<?php

namespace App\Services\Scheduler;

class ScheduleAutoFixService
{
    public function __construct(private ScheduleGeneratorService $generator) {}

    /**
     * Run multi-pass auto-fix.
     *
     * @param array $schedule  ['sessions' => [...], 'conflicts' => [...]]
     * @param array $payload   Original generator payload (sections/teachers/rooms/policy/time/weights)
     * @param int   $maxPasses
     */
    public function fix(array $schedule, array $payload, int $maxPasses = 3): array
    {
        $current = $schedule;
        $previousScore = -INF;

        for ($pass = 1; $pass <= $maxPasses; $pass++) {
            [$clean, $problematic] = $this->partition($current['sessions'] ?? []);

            if (empty($problematic)) break;

            // Reduce payload to only sections/subjects of problematic sessions
            $subPayload = $this->reducePayload($payload, $problematic, $clean);
            $regen = $this->generator->generate($subPayload, 4);
            $best  = $regen['options'][0] ?? ['sessions' => [], 'conflicts' => []];
            $adjusted = array_map(function (array $session) {
                if (($session['status'] ?? 'valid') === 'valid') {
                    $session['status'] = 'adjusted';
                }
                return $session;
            }, $best['sessions'] ?? []);

            $merged = array_merge($clean, $adjusted);
            // Subject-cap dedup: never end up with more sessions for a
            // (section_id, subject_id) pair than units * 1hr (i.e. totalHrs).
            // Without this, regenerating a subject that was partially in
            // $clean (some valid + some conflict) would re-place the full
            // hour target, producing duplicate sessions.
            $merged = $this->capBySubjectHours($merged, $payload);
            // Hard dedup any exact (section,day,start) collisions.
            $merged = $this->dedupExact($merged);
            // re-detect conflicts on merged
            $merged = $this->markConflicts($merged);

            $score = $this->generator->scoreSchedule($merged, $payload['weights'] ?? [], $payload['time'] ?? []);

            $current = [
                'sessions' => $merged,
                'conflicts' => array_values(array_filter($merged, fn($s) => $s['status'] === 'conflict')),
                'score' => $score,
                'pass' => $pass,
            ];

            if ($score <= $previousScore) break;
            $previousScore = $score;
        }

        return $current;
    }

    private function partition(array $sessions): array
    {
        $clean = [];
        $bad   = [];
        foreach ($sessions as $s) {
            if (($s['status'] ?? 'valid') === 'conflict') $bad[] = $s; else $clean[] = $s;
        }
        return [$clean, $bad];
    }

    private function reducePayload(array $payload, array $problematic, array $clean): array
    {
        $needed = [];
        foreach ($problematic as $s) {
            $needed[$s['section_id']][] = $s['subject_id'];
        }

        // How many hours of each (section, subject) are already placed and valid.
        // We must subtract these from the subject's weekly target so the
        // regenerator doesn't re-place the FULL target on top of what's there
        // (which produced 4.5hr / 6hr duplicates for 3-unit subjects).
        $cleanHours = [];
        foreach ($clean as $s) {
            $key = ($s['section_id'] ?? '') . '|' . ($s['subject_id'] ?? '');
            $hrs = (strtotime($s['end_time']) - strtotime($s['start_time'])) / 3600;
            $cleanHours[$key] = ($cleanHours[$key] ?? 0) + $hrs;
        }

        $sections = [];
        foreach (($payload['sections'] ?? []) as $sec) {
            if (! isset($needed[$sec['id']])) continue;
            $subjectIds = array_unique($needed[$sec['id']]);
            $subjects = [];
            foreach (($sec['subjects'] ?? []) as $sub) {
                if (! in_array($sub['id'], $subjectIds, true)) continue;
                $target = (float) ($sub['hours'] ?? max(1, (float) ($sub['units'] ?? 3)));
                $already = $cleanHours[$sec['id'] . '|' . $sub['id']] ?? 0;
                $remaining = $target - $already;
                if ($remaining <= 0.01) continue; // already fully covered
                $sub['hours'] = round($remaining, 2);
                $subjects[] = $sub;
            }
            if (empty($subjects)) continue;
            $sec['subjects'] = $subjects;
            $sections[] = $sec;
        }

        $payload['sections'] = $sections;
        $payload['time']['existing_sessions'] = array_values($clean);
        return $payload;
    }

    private function markConflicts(array $sessions): array
    {
        $teacher = $room = $section = [];
        foreach ($sessions as $i => $s) {
            $sessions[$i]['status'] = 'valid';
            $sessions[$i]['conflict_reasons'] = [];
        }
        foreach ($sessions as $i => $s) {
            $key = fn($id, $day) => $id . '|' . $day;
            $checks = [
                ['teacher_id', $teacher, 'Teacher overlap'],
                ['room_id',    $room,    'Room overlap'],
                ['section_id', $section, 'Section overlap'],
            ];
            foreach ($checks as [$field, &$bag, $msg]) {
                if (empty($s[$field])) continue;
                $k = $key($s[$field], $s['day_of_week']);
                foreach ($bag[$k] ?? [] as $j) {
                    $o = $sessions[$j];
                    if (! ($s['end_time'] <= $o['start_time'] || $s['start_time'] >= $o['end_time'])) {
                        $sessions[$i]['status'] = 'conflict';
                        $sessions[$i]['conflict_reasons'][] = $msg;
                        $sessions[$j]['status'] = 'conflict';
                        $sessions[$j]['conflict_reasons'][] = $msg;
                    }
                }
                $bag[$k][] = $i;
            }
        }
        return $sessions;
    }

    /**
     * Drop any exact (section_id, day_of_week, start_time) duplicates,
     * keeping the first occurrence. Defensive net for upstream merging.
     */
    private function dedupExact(array $sessions): array
    {
        $seen = [];
        $out  = [];
        foreach ($sessions as $s) {
            $key = ($s['section_id'] ?? '') . '|' . ($s['day_of_week'] ?? '') . '|' . ($s['start_time'] ?? '');
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[] = $s;
        }
        return $out;
    }

    /**
     * Drop sessions for a (section_id, subject_id) pair beyond the subject's
     * weekly hour target (units * 1hr, or explicit `hours` from payload).
     * Keeps the first sessions in array order; later duplicates discarded.
     */
    private function capBySubjectHours(array $sessions, array $payload): array
    {
        $caps = [];
        foreach (($payload['sections'] ?? []) as $sec) {
            foreach (($sec['subjects'] ?? []) as $sub) {
                $hours = (float) ($sub['hours'] ?? max(1, (float) ($sub['units'] ?? 3)));
                $caps[($sec['id'] ?? '') . '|' . ($sub['id'] ?? '')] = $hours;
            }
        }
        if (empty($caps)) return $sessions;

        $accum = [];
        $out = [];
        foreach ($sessions as $s) {
            $key = ($s['section_id'] ?? '') . '|' . ($s['subject_id'] ?? '');
            $cap = $caps[$key] ?? null;
            if ($cap === null) {
                $out[] = $s;
                continue;
            }
            $hrs = (strtotime($s['end_time']) - strtotime($s['start_time'])) / 3600;
            $running = ($accum[$key] ?? 0);
            if ($running + $hrs > $cap + 0.01) continue; // exceeds cap → drop
            $accum[$key] = $running + $hrs;
            $out[] = $s;
        }
        return $out;
    }
}
