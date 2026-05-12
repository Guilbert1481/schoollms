<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Ensures every active program_subject has at least one qualified teacher.
 *
 * Logic:
 *  1) Scan active program_subjects for subjects with no row in teacher_subjects.
 *  2) Group teachers by their dominant subject-code prefix (e.g. ENG, MATH, PROF).
 *  3) For each uncovered subject, pick the teacher whose existing prefix matches
 *     the subject's prefix and who currently teaches the fewest subjects.
 *  4) Fall back to the lightest-loaded teacher overall if no prefix match.
 *  5) Idempotent — skips if assignment already exists.
 */
class AutoAssignTeacherSubjects extends Command
{
    protected $signature = 'teachers:auto-cover-subjects
                            {--dry-run : Show what would change without writing}
                            {--school= : Restrict to a school_id}';

    protected $description = 'Auto-assign teachers to active program subjects without a qualified teacher.';

    public function handle(): int
    {
        $schoolId = $this->option('school') ? (int) $this->option('school') : null;
        $dry = (bool) $this->option('dry-run');

        $activeSubjectIds = DB::table('program_subjects as ps')
            ->join('programs as p', 'p.id', '=', 'ps.program_id')
            ->where('ps.is_active', 1)
            ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId))
            ->pluck('ps.subject_id')
            ->unique();

        if ($activeSubjectIds->isEmpty()) {
            $this->info('No active program_subjects.');
            return self::SUCCESS;
        }

        $covered = DB::table('teacher_subjects')
            ->whereIn('subject_id', $activeSubjectIds)
            ->pluck('subject_id')
            ->unique();

        $uncovered = $activeSubjectIds->diff($covered)->values();
        if ($uncovered->isEmpty()) {
            $this->info('All active program subjects are already covered.');
            return self::SUCCESS;
        }

        $subjects = DB::table('subjects')->whereIn('id', $uncovered)->get(['id', 'code', 'name'])->keyBy('id');

        // Build teacher pool with prefix profile
        $teacherPool = DB::table('teachers as t')
            ->when($schoolId, fn ($q) => $q->where('t.school_id', $schoolId))
            ->leftJoin('teacher_subjects as ts', 'ts.teacher_id', '=', 't.id')
            ->leftJoin('subjects as s', 's.id', '=', 'ts.subject_id')
            ->select('t.id', 's.code')
            ->get()
            ->groupBy('id');

        if ($teacherPool->isEmpty()) {
            $this->error('No teachers found.');
            return self::FAILURE;
        }

        $teacherPrefixes = []; // tid => [prefix => count]
        $teacherLoad = [];     // tid => total subjects
        foreach ($teacherPool as $tid => $rows) {
            $teacherPrefixes[$tid] = [];
            $teacherLoad[$tid] = 0;
            foreach ($rows as $r) {
                if (! $r->code) continue;
                $prefix = $this->prefix($r->code);
                $teacherPrefixes[$tid][$prefix] = ($teacherPrefixes[$tid][$prefix] ?? 0) + 1;
                $teacherLoad[$tid]++;
            }
        }

        $assigned = 0;
        $rows = [];

        foreach ($uncovered as $subjectId) {
            $subj = $subjects->get($subjectId);
            if (! $subj) continue;
            $prefix = $this->prefix($subj->code);

            // Rank teachers: prefer those whose dominant prefix matches; tiebreak by lightest load.
            $ranked = collect($teacherLoad)->map(function ($load, $tid) use ($teacherPrefixes, $prefix) {
                $matchScore = ($teacherPrefixes[$tid][$prefix] ?? 0);
                return [
                    'tid' => $tid,
                    'match' => $matchScore,
                    'load' => $load,
                ];
            })->values()
                ->sortBy([
                    ['match', 'desc'],
                    ['load', 'asc'],
                ])
                ->values();

            $pick = $ranked->first();
            if (! $pick) continue;

            $tid = $pick['tid'];
            $rows[] = [
                'teacher_id' => $tid,
                'subject_id' => $subjectId,
                'subject' => $subj->code . ' — ' . $subj->name,
                'match' => $pick['match'],
                'load' => $pick['load'],
            ];

            // Update in-memory load so next iteration distributes fairly
            $teacherLoad[$tid]++;
            $teacherPrefixes[$tid][$prefix] = ($teacherPrefixes[$tid][$prefix] ?? 0) + 1;
        }

        $this->table(
            ['teacher_id', 'subject_id', 'subject', 'prefix_match', 'prior_load'],
            array_map(fn ($r) => [$r['teacher_id'], $r['subject_id'], $r['subject'], $r['match'], $r['load']], $rows)
        );

        if ($dry) {
            $this->info('Dry-run: no changes written.');
            return self::SUCCESS;
        }

        $now = now();
        foreach ($rows as $r) {
            DB::table('teacher_subjects')->insert([
                'teacher_id' => $r['teacher_id'],
                'subject_id' => $r['subject_id'],
                'is_primary' => 0,
                'qualification_level' => 'secondary',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $assigned++;
        }

        $this->info("Assigned {$assigned} teacher_subject row(s).");
        return self::SUCCESS;
    }

    private function prefix(?string $code): string
    {
        if (! $code) return '';
        $parts = preg_split('/[-_\s]+/', strtoupper($code));
        return $parts[0] ?? '';
    }
}
