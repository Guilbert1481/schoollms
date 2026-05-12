<?php

namespace App\Http\Controllers\Scheduler;

use App\Http\Controllers\Controller;
use App\Models\Scheduler\SchedulerSetting;
use App\Services\Scheduler\GeneticSchedulerService;
use App\Services\Scheduler\ScheduleGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GenerateScheduleController extends Controller
{
    public function __invoke(
        Request $request,
        ScheduleGeneratorService $generator,
        GeneticSchedulerService $genetic
    ) {
        $payload = $this->normalize($request->all());

        $this->persistTeacherConstraints(
            optional($request->user())->school_id,
            $payload['teacher_constraints']
        );

        $advanced = (bool) $request->input('advanced', false);
        $result = $advanced
            ? $genetic->optimize($payload)
            : $generator->generate($payload, (int) $request->input('attempts', 8));

        return response()->json([
            'ok'      => true,
            'options' => $result['options'] ?? [],
            'payload' => $payload,
        ]);
    }

    private function normalize(array $data): array
    {
        $tc = is_array($data['teacher_constraints'] ?? null) ? $data['teacher_constraints'] : [];

        return [
            'policy'   => $data['policy']   ?? [],
            'section_policy' => is_array($data['section_policy'] ?? null) ? $data['section_policy'] : [],
            'teacher_constraints' => [
                'max_hours_per_week'   => isset($tc['max_hours_per_week'])   ? (int) $tc['max_hours_per_week']   : 24,
                'max_hours_per_day'    => isset($tc['max_hours_per_day'])    ? (int) $tc['max_hours_per_day']    : 5,
                'work_days_per_week'   => isset($tc['work_days_per_week'])   ? (int) $tc['work_days_per_week']   : 5,
                'min_hours_per_day'    => isset($tc['min_hours_per_day'])    ? (float) $tc['min_hours_per_day']  : 1.0,
                'prioritize_full_time' => isset($tc['prioritize_full_time']) ? (bool) $tc['prioritize_full_time'] : true,
                'part_time_min_hours_per_day' => isset($tc['part_time_min_hours_per_day']) ? (float) $tc['part_time_min_hours_per_day'] : 1.0,
            ],
            'time'     => $data['time']     ?? [],
            'weights'  => $data['weights']  ?? [],
            'sections' => array_values($data['sections'] ?? []),
            'teachers' => array_values($data['teachers'] ?? []),
            'rooms'    => array_values($data['rooms']    ?? []),
        ];
    }

    private function persistTeacherConstraints(?int $schoolId, array $tc): void
    {
        if (! $schoolId) return;
        if (! Schema::hasTable('scheduler_settings')) return;
        if (! Schema::hasColumn('scheduler_settings', 'teacher_max_hours_per_week')) return;

        $updates = [
            'teacher_max_hours_per_week' => (int) $tc['max_hours_per_week'],
            'teacher_max_hours_per_day'  => (int) $tc['max_hours_per_day'],
            'teacher_work_days_per_week' => (int) $tc['work_days_per_week'],
            'prioritize_full_time'       => (bool) $tc['prioritize_full_time'],
        ];

        if (Schema::hasColumn('scheduler_settings', 'teacher_min_hours_per_day')) {
            $updates['teacher_min_hours_per_day'] = (float) $tc['min_hours_per_day'];
        }
        if (Schema::hasColumn('scheduler_settings', 'part_time_min_hours_per_day')) {
            $updates['part_time_min_hours_per_day'] = (float) $tc['part_time_min_hours_per_day'];
        }

        SchedulerSetting::updateOrCreate(['school_id' => $schoolId], $updates);
    }
}
