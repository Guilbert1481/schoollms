<?php

namespace App\Services\Academics;

use Illuminate\Support\Facades\DB;

/**
 * Bridges the Scheduler's generated timetables (schedules + schedule_sessions,
 * keyed by section/subject) into the per-class weekly pattern (class_schedules)
 * that the student timetable page, the dashboard today-widget, and the
 * class-session generator read. Without this bridge the two systems never
 * meet and student schedules stay empty forever.
 *
 * A session maps to its class through the classes unique key
 * (school_id, subject_id, term_id, section_id), so the resolution is exact.
 * Conflict sessions are skipped; every class of a covered section gets its
 * weekly pattern replaced so stale rows from a previous schedule vanish.
 */
class ClassScheduleSync
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    /** Sync one applied schedule into class_schedules. Returns rows written. */
    public function sync(int $scheduleId): int
    {
        $schedule = DB::table('schedules')->where('id', $scheduleId)->first();
        if (! $schedule) {
            return 0;
        }

        $sessions = DB::table('schedule_sessions')
            ->where('schedule_id', $schedule->id)
            ->where('status', '!=', 'conflict')
            ->get(['section_id', 'subject_id', 'day_of_week', 'start_time', 'end_time']);

        if ($sessions->isEmpty()) {
            return 0;
        }

        // Every class of the covered sections — cleared even when the new
        // schedule dropped a subject, so stale patterns don't linger.
        $classes = DB::table('classes')
            ->where('school_id', $schedule->school_id)
            ->where('term_id', $schedule->term_id)
            ->whereIn('section_id', $sessions->pluck('section_id')->unique())
            ->get(['id', 'section_id', 'subject_id'])
            ->keyBy(fn ($c) => $c->section_id.':'.$c->subject_id);

        $rows = [];
        foreach ($sessions as $s) {
            $class = $classes->get($s->section_id.':'.$s->subject_id);
            // Scheduler stores lowercase day names; class_schedules enum is capitalised.
            $day = ucfirst(strtolower((string) $s->day_of_week));
            if (! $class || ! in_array($day, self::DAYS, true)) {
                continue;
            }
            $rows[] = [
                'class_id' => $class->id,
                'day_of_week' => $day,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return DB::transaction(function () use ($classes, $rows) {
            DB::table('class_schedules')->whereIn('class_id', $classes->pluck('id'))->delete();
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('class_schedules')->insert($chunk);
            }

            return count($rows);
        });
    }

    /**
     * Sync every active schedule (optionally one school's) — the backfill for
     * timetables applied before this bridge existed.
     *
     * @return array<int, int> schedule id => rows written
     */
    public function syncActive(?int $schoolId = null): array
    {
        $ids = DB::table('schedules')
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('id');

        $out = [];
        foreach ($ids as $id) {
            $out[(int) $id] = $this->sync((int) $id);
        }

        return $out;
    }
}
