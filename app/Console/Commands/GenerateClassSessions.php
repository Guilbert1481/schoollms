<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Models\Term;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Expand `class_schedules` (subject + day-of-week + start/end time) over a
 * term's [start_date, end_date] range into concrete `class_sessions` rows
 * (one per actual meeting). Idempotent: re-running for the same term will
 * skip dates that already have a session for the same class.
 *
 * Usage:
 *   php artisan acad:generate-sessions {term_id} [--class=ID] [--prune] [--dry-run]
 */
class GenerateClassSessions extends Command
{
    protected $signature = 'acad:generate-sessions
                            {term_id : ID of the term whose schedules will be expanded}
                            {--class= : Limit to a single class_id}
                            {--prune : Delete future scheduled sessions outside current schedules before generating}
                            {--dry-run : Compute and report counts without writing rows}';

    protected $description = 'Generate class_sessions rows from class_schedules across the term date range';

    public function handle(): int
    {
        $termId = (int) $this->argument('term_id');
        $term   = Term::find($termId);

        if (!$term) {
            $this->error("Term #{$termId} not found.");
            return self::FAILURE;
        }

        if (!$term->start_date || !$term->end_date) {
            $this->error("Term #{$termId} has no start_date/end_date.");
            return self::FAILURE;
        }

        $start = CarbonImmutable::parse($term->start_date)->startOfDay();
        $end   = CarbonImmutable::parse($term->end_date)->startOfDay();

        if ($end->lt($start)) {
            $this->error("Term end_date is before start_date.");
            return self::FAILURE;
        }

        // Pull schedules for all classes in this term (optionally filtered)
        $schedulesQuery = DB::table('class_schedules as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->where('c.term_id', $term->id)
            ->select([
                'cs.id as schedule_id',
                'cs.class_id',
                'cs.day_of_week',
                'cs.start_time',
                'cs.end_time',
                'c.subject_id',
                'c.teacher_id',
                'c.max_students',
            ]);

        if ($classId = $this->option('class')) {
            $schedulesQuery->where('c.id', (int) $classId);
        }

        $schedules = $schedulesQuery->get();

        if ($schedules->isEmpty()) {
            $this->warn("No class_schedules found for term #{$term->id}" .
                ($classId ? " / class #{$classId}" : '') . '.');
            return self::SUCCESS;
        }

        $period = CarbonPeriod::create($start, $end);
        $dryRun = (bool) $this->option('dry-run');
        $prune  = (bool) $this->option('prune');

        $created = 0;
        $skipped = 0;
        $pruned  = 0;

        DB::transaction(function () use (
            $term, $schedules, $period, $dryRun, $prune,
            &$created, &$skipped, &$pruned, $classId
        ) {
            // Optional pruning: remove future scheduled sessions for these classes
            // so re-running after a schedule edit gives a clean result.
            if ($prune) {
                $today = now()->toDateString();
                $classIds = $schedules->pluck('class_id')->unique()->values();

                $pruneQuery = ClassSession::query()
                    ->whereIn('class_id', $classIds)
                    ->where('meeting_date', '>=', $today)
                    ->where('status', ClassSession::STATUS_SCHEDULED);

                $pruned = (clone $pruneQuery)->count();

                if (!$dryRun) {
                    $pruneQuery->delete();
                }
            }

            // Group schedules by class_id for efficient existence checks
            $byClass = $schedules->groupBy('class_id');

            foreach ($byClass as $classId => $classSchedules) {
                // Pre-fetch existing meeting_dates for this class to skip duplicates
                $existing = ClassSession::query()
                    ->where('class_id', $classId)
                    ->pluck('meeting_date')
                    ->map(fn ($d) => CarbonImmutable::parse($d)->toDateString())
                    ->flip();

                $rows = [];

                foreach ($period as $date) {
                    /** @var CarbonImmutable $date */
                    $dayName = $date->englishDayOfWeek; // "Monday".."Sunday"

                    foreach ($classSchedules as $sched) {
                        if ($sched->day_of_week !== $dayName) {
                            continue;
                        }

                        $dateStr = $date->toDateString();

                        if ($existing->has($dateStr)) {
                            // Class already has SOME session that day; check if
                            // a session matching this exact start_time exists.
                            $clash = ClassSession::query()
                                ->where('class_id', $sched->class_id)
                                ->where('meeting_date', $dateStr)
                                ->where('start_time', $sched->start_time)
                                ->exists();

                            if ($clash) {
                                $skipped++;
                                continue;
                            }
                        }

                        $rows[] = [
                            'class_id'     => $sched->class_id,
                            'subject_id'   => $sched->subject_id,
                            'teacher_id'   => $sched->teacher_id,
                            'room'         => null,
                            'meeting_date' => $dateStr,
                            'start_time'   => $sched->start_time,
                            'end_time'     => $sched->end_time,
                            'capacity'     => $sched->max_students ?: null,
                            'status'       => ClassSession::STATUS_SCHEDULED,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ];
                    }
                }

                if (!empty($rows)) {
                    $created += count($rows);
                    if (!$dryRun) {
                        // Chunk inserts to keep packet size reasonable
                        foreach (array_chunk($rows, 500) as $chunk) {
                            DB::table('class_sessions')->insert($chunk);
                        }
                    }
                }
            }
        });

        $mode = $dryRun ? '[dry-run] ' : '';
        $this->info("{$mode}Term #{$term->id} ({$term->name})");
        $this->line("  date range : {$start->toDateString()} → {$end->toDateString()}");
        $this->line("  schedules  : " . $schedules->count());
        $this->line("  created    : {$created}");
        $this->line("  skipped    : {$skipped} (already existed)");
        if ($prune) {
            $this->line("  pruned     : {$pruned}");
        }

        return self::SUCCESS;
    }
}
