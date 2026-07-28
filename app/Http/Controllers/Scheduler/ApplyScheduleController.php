<?php

namespace App\Http\Controllers\Scheduler;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplyScheduleController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'sessions' => ['nullable', 'array'],
            'score' => ['nullable', 'numeric'],
            'name' => ['nullable', 'string', 'max:255'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'meta' => ['nullable', 'array'],
        ]);

        $sessions = $data['sessions'] ?? [];
        $score = (float) ($data['score'] ?? 0);
        $name = $data['name'] ?? 'Generated Schedule';
        $termId = (int) $data['term_id'];
        $meta = $data['meta'] ?? [];
        $schoolId = optional($request->user())->school_id;

        if (! Schema::hasTable('schedules') || ! Schema::hasTable('schedule_sessions')) {
            return response()->json(['ok' => false, 'message' => 'Scheduler tables not migrated.'], 500);
        }

        return DB::transaction(function () use ($sessions, $score, $name, $termId, $meta, $schoolId, $request) {
            // Determine version
            $latest = DB::table('schedules')
                ->where('school_id', $schoolId)
                ->where('term_id', $termId)
                ->max('version');
            $version = (int) ($latest ?? 0) + 1;

            // Deactivate previous
            DB::table('schedules')
                ->where('school_id', $schoolId)
                ->where('term_id', $termId)
                ->update(['is_active' => false, 'updated_at' => now()]);

            $scheduleId = DB::table('schedules')->insertGetId([
                'school_id' => $schoolId,
                'term_id' => $termId,
                'name' => $name,
                'version' => $version,
                'score' => $score,
                'is_active' => true,
                'meta' => json_encode($meta),
                'created_by' => optional($request->user())->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = [];
            $seen = [];
            foreach ($sessions as $s) {
                // Defensive dedup: same (section, day, start_time) → keep only first.
                $key = ($s['section_id'] ?? '').'|'.($s['day_of_week'] ?? '').'|'.($s['start_time'] ?? '');
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $rows[] = [
                    'schedule_id' => $scheduleId,
                    'section_id' => $s['section_id'] ?? null,
                    'subject_id' => $s['subject_id'] ?? null,
                    'teacher_id' => $s['teacher_id'] ?? null,
                    'room_id' => $s['room_id'] ?? null,
                    'day_of_week' => $s['day_of_week'] ?? '',
                    'start_time' => $s['start_time'] ?? '00:00',
                    'end_time' => $s['end_time'] ?? '00:00',
                    'status' => $s['status'] ?? 'valid',
                    'conflict_reasons' => json_encode($s['conflict_reasons'] ?? []),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($rows) {
                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table('schedule_sessions')->insert($chunk);
                }
            }

            // Push the applied timetable into class_schedules so student
            // weekly schedules and class-session generation pick it up.
            app(\App\Services\Academics\ClassScheduleSync::class)->sync($scheduleId);

            return response()->json([
                'ok' => true,
                'schedule_id' => $scheduleId,
                'version' => $version,
            ]);
        });
    }
}
