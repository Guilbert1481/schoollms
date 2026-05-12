<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use Illuminate\Support\Facades\DB;

/**
 * Detects time-overlap conflicts among the student's selected classes.
 *
 * Two schedules conflict when they share a day_of_week AND their
 * [start_time, end_time) intervals overlap. (Equal endpoints don't count.)
 */
class ScheduleConflictValidator implements EnrolmentValidator
{
    public function key(): string
    {
        return 'schedule_conflict';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        if ($ctx->selectedClassIds->count() < 2) {
            return ValidationResult::pass();
        }

        $rows = DB::table('class_schedules as sch')
            ->join('classes as c', 'c.id', '=', 'sch.class_id')
            ->join('subjects as sub', 'sub.id', '=', 'c.subject_id')
            ->whereIn('sch.class_id', $ctx->selectedClassIds)
            ->select([
                'sch.class_id',
                'sch.day_of_week',
                'sch.start_time',
                'sch.end_time',
                'sub.name as subject_name',
            ])
            ->get();

        $result = ValidationResult::pass();

        $byDay = $rows->groupBy('day_of_week');

        foreach ($byDay as $day => $items) {
            $items = $items->values();
            $count = $items->count();

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = $items[$i];
                    $b = $items[$j];

                    if ($a->class_id === $b->class_id) {
                        continue;
                    }

                    if ($a->start_time < $b->end_time && $b->start_time < $a->end_time) {
                        $result->add(
                            ValidationResult::SEVERITY_FAIL,
                            "Schedule conflict on {$day}: \"{$a->subject_name}\" ({$a->start_time}-{$a->end_time}) overlaps \"{$b->subject_name}\" ({$b->start_time}-{$b->end_time}).",
                            [
                                'day'        => $day,
                                'class_a_id' => $a->class_id,
                                'class_b_id' => $b->class_id,
                            ]
                        );
                    }
                }
            }
        }

        return $result;
    }
}
