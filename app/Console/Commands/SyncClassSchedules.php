<?php

namespace App\Console\Commands;

use App\Services\Academics\ClassScheduleSync;
use Illuminate\Console\Command;

/**
 * Backfill class_schedules from the currently active Scheduler timetables.
 * New applications sync automatically (ApplyScheduleController); this command
 * covers schedules applied before the bridge existed.
 */
class SyncClassSchedules extends Command
{
    protected $signature = 'schedule:sync-class-schedules {--school= : Only sync this school id}';

    protected $description = 'Sync active Scheduler timetables into class_schedules (student weekly schedule)';

    public function handle(ClassScheduleSync $sync): int
    {
        $schoolId = $this->option('school') !== null ? (int) $this->option('school') : null;

        $results = $sync->syncActive($schoolId);

        if ($results === []) {
            $this->warn('No active schedules found — nothing to sync.');

            return self::SUCCESS;
        }

        foreach ($results as $id => $count) {
            $this->info("Schedule #{$id}: {$count} class_schedules rows written.");
        }

        return self::SUCCESS;
    }
}
