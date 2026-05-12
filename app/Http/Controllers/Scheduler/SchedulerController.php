<?php

namespace App\Http\Controllers\Scheduler;

use App\Http\Controllers\Controller;
use App\Models\Scheduler\AcademicScheduler;
use App\Models\Scheduler\SchedulerSetting;
use App\Services\Scheduler\TeacherCoverageReport;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchedulerController extends Controller
{
    public function index(Request $request, TeacherCoverageReport $coverage)
    {
        $schoolId = optional($request->user())->school_id;
        $termId = $this->resolveTermId($request, $schoolId);
        $terms = $this->fetchTerms($schoolId);

        $sections = $this->fetchSections($schoolId, $termId);
        $subjects = $this->fetchSubjects($schoolId);
        $teachers = $this->fetchTeachers($schoolId);
        $rooms    = $this->fetchRooms($schoolId);
        $sectionSubjects = $this->fetchSectionSubjects($sections, $subjects);
        $teacherCoverage = $coverage->generate($schoolId);

        $policy = Schema::hasTable('scheduler_settings')
            ? SchedulerSetting::forSchool($schoolId)
            : [
                'min_session_hours' => 1,
                'max_session_hours' => 2,
                'max_subjects_per_day' => 5,
                'max_hours_per_week' => 40,
                'max_allowed_gap' => 30,
                'allow_gaps' => true,
                'min_days_per_week' => 1,
                'max_days_per_week' => 3,
            ];

        $teacherConstraints = Schema::hasTable('scheduler_settings')
            && Schema::hasColumn('scheduler_settings', 'teacher_max_hours_per_week')
            ? SchedulerSetting::teacherConstraintsForSchool($schoolId)
            : [
                'max_hours_per_week'   => 24,
                'max_hours_per_day'    => 5,
                'work_days_per_week'   => 5,
                'min_hours_per_day'    => 1,
                'prioritize_full_time' => true,
                'part_time_min_hours_per_day' => 1,
            ];

        $defaults = [
            'policy' => $policy,
            'teacher_constraints' => $teacherConstraints,
            'time' => [
                'days_of_week' => ['monday','tuesday','wednesday','thursday','friday'],
                'start_time'   => '07:00',
                'end_time'     => '17:00',
                'slot_duration'=> 30,
                'break_time'   => ['start' => '12:00', 'end' => '13:00'],
            ],
            'weights' => [
                'gap' => 1.0,
                'compact' => 1.0,
                'teacher' => 1.0,
                'room' => 1.0,
            ],
        ];

        $activeSchedule = $this->fetchActiveSchedule($schoolId, $termId, $sections, $subjects, $teachers, $rooms);
        $savedTime = Arr::get($activeSchedule, 'meta.time')
            ?? Arr::get($activeSchedule, 'meta.payload.time');
        if (is_array($savedTime)) {
            $defaults['time'] = array_replace_recursive($defaults['time'], $savedTime);
        }

        // Restore previously saved scheduler config (per school+term) so refresh
        // doesn't blow away user's policy / section_policy / teacher_constraints / time tweaks.
        if (Schema::hasTable('academic_schedulers')) {
            $saved = AcademicScheduler::where('school_id', $schoolId)
                ->where('term_id', $termId)
                ->first();
            if ($saved && is_array($saved->config)) {
                foreach (['policy', 'section_policy', 'teacher_constraints', 'time', 'weights'] as $key) {
                    if (isset($saved->config[$key]) && is_array($saved->config[$key])) {
                        $defaults[$key] = array_replace_recursive($defaults[$key] ?? [], $saved->config[$key]);
                    }
                }
            }
        }

        return view('tools.scheduler.index', compact(
            'sections',
            'subjects',
            'teachers',
            'rooms',
            'sectionSubjects',
            'defaults',
            'teacherCoverage',
            'terms',
            'termId',
            'activeSchedule'
        ));
    }

    public function saveConfig(Request $request)
    {
        $schoolId = optional($request->user())->school_id;
        $termId   = $request->integer('term_id') ?: null;

        $config = [
            'policy'              => $request->input('policy', []),
            'section_policy'      => $request->input('section_policy', []),
            'teacher_constraints' => $request->input('teacher_constraints', []),
            'time'                => $request->input('time', []),
            'weights'             => $request->input('weights', []),
        ];

        AcademicScheduler::updateOrCreate(
            ['school_id' => $schoolId, 'term_id' => $termId],
            ['config' => $config]
        );

        return response()->json(['ok' => true]);
    }

    private function resolveTermId(Request $request, ?int $schoolId): ?int
    {
        if ($request->filled('term_id')) {
            return $request->integer('term_id');
        }

        if (! Schema::hasTable('terms')) {
            return null;
        }

        return optional(
            DB::table('terms')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderByDesc('is_current')
                ->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 0 END DESC")
                ->orderByDesc('start_date')
                ->first()
        )->id;
    }

    private function fetchTerms(?int $schoolId): array
    {
        if (! Schema::hasTable('terms')) {
            return [];
        }

        return DB::table('terms')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get(['id', 'name'])
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function fetchSections(?int $schoolId, ?int $termId): array
    {
        if (! Schema::hasTable('sections')) return [];
        $q = DB::table('sections');
        if (Schema::hasColumn('sections', 'is_active')) {
            $q->where('is_active', true);
        }
        if ($schoolId && Schema::hasColumn('sections', 'school_id')) $q->where('school_id', $schoolId);
        if ($termId) {
            if (Schema::hasColumn('sections', 'term_id')) {
                $q->where('term_id', $termId);
            } elseif (Schema::hasColumn('sections', 'semester_id')) {
                $q->where('semester_id', $termId);
            }
        }

        $cols = ['id', 'name'];
        foreach (['code','year_level','program_id','term_id','semester_id','capacity'] as $c) {
            if (Schema::hasColumn('sections', $c)) $cols[] = $c;
        }
        return $q->select($cols)->orderBy('name')->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function fetchSubjects(?int $schoolId): array
    {
        if (! Schema::hasTable('subjects')) return [];
        $q = DB::table('subjects');
        if ($schoolId && Schema::hasColumn('subjects', 'school_id')) $q->where('school_id', $schoolId);
        if (Schema::hasColumn('subjects', 'is_active')) $q->where('is_active', true);
        // legacy 'active' column removed; only 'is_active' is used now

        $cols = ['id','name'];
        foreach (['code','units','type','is_lab'] as $c) if (Schema::hasColumn('subjects', $c)) $cols[] = $c;
        $rows = $q->select($cols)->orderBy('name')->get()->map(fn($r) => (array) $r)->toArray();

        foreach ($rows as &$r) {
            $explicitLab = ! empty($r['is_lab'] ?? null) || strcasecmp((string)($r['type'] ?? ''), 'lab') === 0;
            $nameLab     = (bool) preg_match('/\b(lab|laboratory)\b/i', $r['name'] ?? '');
            $r['is_lab'] = $explicitLab || $nameLab;
        }
        unset($r);

        return $rows;
    }

    private function fetchTeachers(?int $schoolId): array
    {
        if (! Schema::hasTable('teacher_subjects')) return [];

        // teacher_subjects.teacher_id references the `teachers` table (which has
        // employment_type), not `teacher_profiles`. Prefer `teachers` so FT/PT
        // distinction works correctly.
        $teacherTable = Schema::hasTable('teachers') ? 'teachers' : (Schema::hasTable('teacher_profiles') ? 'teacher_profiles' : null);

        $tsCols = ['teacher_id', 'subject_id'];
        if (Schema::hasColumn('teacher_subjects', 'is_primary')) $tsCols[] = 'is_primary';

        $assignmentsQuery = DB::table('teacher_subjects')->select($tsCols);
        if ($schoolId && Schema::hasColumn('teacher_subjects', 'school_id')) {
            $assignmentsQuery->where('school_id', $schoolId);
        }
        $assignments = $assignmentsQuery->get();

        $bySubj = [];
        $primary = [];
        foreach ($assignments as $a) {
            $bySubj[$a->teacher_id][] = (int) $a->subject_id;
            if (! empty($a->is_primary ?? false)) {
                $primary[$a->teacher_id][] = (int) $a->subject_id;
            }
        }
        if (empty($bySubj)) return [];

        $teachers = [];
        if ($teacherTable) {
            $rowsQuery = DB::table($teacherTable)->whereIn($teacherTable . '.id', array_keys($bySubj));

            // Resolve user link for name + school filter.
            // - teacher_profiles has user_id directly.
            // - teachers links to teacher_profiles via profile_id, which has user_id.
            $hasProfilesJoin = false;
            $hasUsersJoin    = false;
            if ($teacherTable === 'teachers'
                && Schema::hasColumn('teachers', 'profile_id')
                && Schema::hasTable('teacher_profiles')
                && Schema::hasColumn('teacher_profiles', 'user_id')
            ) {
                $rowsQuery->leftJoin('teacher_profiles', 'teacher_profiles.id', '=', 'teachers.profile_id');
                $hasProfilesJoin = true;
            }

            if (Schema::hasTable('users')) {
                if ($teacherTable === 'teacher_profiles' && Schema::hasColumn('teacher_profiles', 'user_id')) {
                    $rowsQuery->leftJoin('users', 'users.id', '=', 'teacher_profiles.user_id');
                    $hasUsersJoin = true;
                } elseif ($hasProfilesJoin) {
                    $rowsQuery->leftJoin('users', 'users.id', '=', 'teacher_profiles.user_id');
                    $hasUsersJoin = true;
                }
            }

            if ($schoolId) {
                if (Schema::hasColumn($teacherTable, 'school_id')) {
                    $rowsQuery->where($teacherTable . '.school_id', $schoolId);
                } elseif ($hasUsersJoin && Schema::hasColumn('users', 'school_id')) {
                    // Permissive: include teachers whose user link is missing
                    // (orphaned profile rows) so they aren't silently dropped.
                    $rowsQuery->where(function ($q) use ($schoolId) {
                        $q->where('users.school_id', $schoolId)
                          ->orWhereNull('users.school_id');
                    });
                }
            }
            if (Schema::hasColumn($teacherTable, 'employment_status')) {
                $rowsQuery->where($teacherTable . '.employment_status', 'active');
            }

            $select = [$teacherTable . '.id'];
            if (Schema::hasColumn($teacherTable, 'employment_type')) {
                $select[] = $teacherTable . '.employment_type';
            }
            if ($hasUsersJoin) {
                foreach (['name', 'first_name', 'last_name', 'full_name'] as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $select[] = 'users.' . $col . ' as ' . $col;
                    }
                }
            }
            $rowsQuery->select($select);

            $rows = $rowsQuery->get();

            foreach ($rows as $r) {
                $teachers[$r->id] = [
                    'id'   => $r->id,
                    'name' => $this->teacherName($r),
                    'subjects' => $bySubj[$r->id] ?? [],
                    'primary_subjects' => $primary[$r->id] ?? [],
                    'employment_type'  => $r->employment_type ?? null,
                    'availability' => [],
                    'preferences'  => null,
                ];
            }
        } else {
            foreach ($bySubj as $tid => $subs) {
                $teachers[$tid] = [
                    'id' => $tid,
                    'name' => "Teacher #$tid",
                    'subjects' => $subs,
                    'primary_subjects' => $primary[$tid] ?? [],
                    'availability' => [],
                    'preferences' => null,
                ];
            }
        }

        if (empty($teachers)) {
            return [];
        }

        if (Schema::hasTable('teacher_availabilities')) {
            $av = DB::table('teacher_availabilities')->whereIn('teacher_id', array_keys($teachers))->get();
            foreach ($av as $a) {
                if (! ($a->is_available ?? 1) || ! isset($teachers[$a->teacher_id])) continue;
                $teachers[$a->teacher_id]['availability'][] = [
                    'day'   => strtolower($a->day_of_week),
                    'start' => substr($a->start_time, 0, 5),
                    'end'   => substr($a->end_time, 0, 5),
                ];
            }
        }

        if (Schema::hasTable('teacher_preferences')) {
            $prefs = DB::table('teacher_preferences')->whereIn('teacher_id', array_keys($teachers))->get();
            foreach ($prefs as $p) {
                if (! isset($teachers[$p->teacher_id])) continue;
                $teachers[$p->teacher_id]['preferences'] = [
                    'preferred_block'    => $p->preferred_block,
                    'max_hours_per_day'  => $p->max_hours_per_day !== null ? (float) $p->max_hours_per_day : null,
                    'max_hours_per_week' => $p->max_hours_per_week !== null ? (float) $p->max_hours_per_week : null,
                ];
            }
        }

        return array_values($teachers);
    }

    private function teacherName(object $row): string
    {
        foreach (['name','full_name','first_name'] as $f) {
            if (! empty($row->{$f} ?? null)) {
                $name = $row->{$f};
                if ($f === 'first_name' && ! empty($row->last_name ?? null)) $name .= ' ' . $row->last_name;
                return $name;
            }
        }

        return 'Teacher #' . $row->id;
    }

    private function fetchRooms(?int $schoolId): array
    {
        if (! Schema::hasTable('rooms')) return [];
        $q = DB::table('rooms');
        if ($schoolId && Schema::hasColumn('rooms', 'school_id')) $q->where('school_id', $schoolId);
        if (Schema::hasColumn('rooms', 'is_active')) $q->where('is_active', true);
        return $q->select('id','name','capacity')->orderBy('name')->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function fetchSectionSubjects(array $sections, array $subjects): array
    {
        if (empty($sections)) return [];

        $subjectIds = array_column($subjects, 'id');
        $rows = [];

        if (Schema::hasTable('section_subjects')) {
            $manual = DB::table('section_subjects')
                ->whereIn('section_id', array_column($sections, 'id'))
                ->get()
                ->map(fn($r) => (array) $r)
                ->toArray();
            foreach ($manual as $m) {
                $key = $m['section_id'] . ':' . $m['subject_id'];
                $rows[$key] = $m + ['source' => 'manual'];
            }
        }

        if (Schema::hasTable('program_subjects')) {
            foreach ($sections as $sec) {
                $programId = $sec['program_id'] ?? null;
                $yearLevel = $sec['year_level'] ?? null;
                if (! $programId) continue;

                $q = DB::table('program_subjects')
                    ->where('program_id', $programId)
                    ->where('is_active', true);

                if ($yearLevel !== null && Schema::hasColumn('program_subjects', 'year_level')) {
                    $q->where('year_level', $yearLevel);
                }
                if (! empty($subjectIds)) {
                    $q->whereIn('subject_id', $subjectIds);
                }

                $auto = $q->get();

                foreach ($auto as $ps) {
                    $key = $sec['id'] . ':' . $ps->subject_id;
                    if (isset($rows[$key])) continue;

                    $units = $this->resolveCurriculumUnits($ps->subject_id, $programId, $yearLevel);

                    $rows[$key] = [
                        'section_id'     => $sec['id'],
                        'subject_id'     => (int) $ps->subject_id,
                        'units'          => $units ?? 3,
                        'hours_per_week' => null,
                        'room_id'        => isset($ps->room_id) && $ps->room_id ? (int) $ps->room_id : null,
                        'source'         => 'program_subjects',
                    ];
                }
            }
        }

        return array_values($rows);
    }

    private function resolveCurriculumUnits(int $subjectId, int $programId, ?int $yearLevel): ?float
    {
        if (! Schema::hasTable('curriculum_subjects') || ! Schema::hasTable('curriculums')) return null;

        $q = DB::table('curriculum_subjects as cs')
            ->join('curriculums as c', 'c.id', '=', 'cs.curriculum_id')
            ->where('cs.subject_id', $subjectId);

        if (Schema::hasColumn('curriculums', 'program_id')) {
            $q->where('c.program_id', $programId);
        }
        if ($yearLevel !== null && Schema::hasColumn('curriculum_subjects', 'year_level')) {
            $q->where('cs.year_level', $yearLevel);
        }
        if (Schema::hasColumn('curriculums', 'is_active')) {
            $q->where('c.is_active', true);
        }

        $units = $q->value('cs.units');
        return $units !== null ? (float) $units : null;
    }

    private function fetchActiveSchedule(
        ?int $schoolId,
        ?int $termId,
        array $sections,
        array $subjects,
        array $teachers,
        array $rooms
    ): ?array {
        if (! $termId || ! Schema::hasTable('schedules') || ! Schema::hasTable('schedule_sessions')) {
            return null;
        }

        $schedule = DB::table('schedules')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('term_id', $termId)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $schedule) {
            return null;
        }

        $sectionNames = collect($sections)->mapWithKeys(fn ($r) => [(int) $r['id'] => $r['name']])->all();
        $subjectNames = collect($subjects)->mapWithKeys(fn ($r) => [(int) $r['id'] => $r['name']])->all();
        $teacherNames = collect($teachers)->mapWithKeys(fn ($r) => [(int) $r['id'] => $r['name']])->all();
        $teacherTypes = collect($teachers)->mapWithKeys(fn ($r) => [(int) $r['id'] => $r['employment_type'] ?? null])->all();
        $roomNames = collect($rooms)->mapWithKeys(fn ($r) => [(int) $r['id'] => $r['name']])->all();

        $sessions = DB::table('schedule_sessions')
            ->where('schedule_id', $schedule->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(function ($row) use ($sectionNames, $subjectNames, $teacherNames, $teacherTypes, $roomNames) {
                $conflictReasons = [];
                if (! empty($row->conflict_reasons)) {
                    $decoded = json_decode($row->conflict_reasons, true);
                    $conflictReasons = is_array($decoded) ? $decoded : [];
                }

                $tid = $row->teacher_id ? (int) $row->teacher_id : null;
                $empType = $tid ? ($teacherTypes[$tid] ?? null) : null;
                $isRegular = $tid !== null
                    ? (! str_contains(strtolower(trim((string) $empType)), 'part'))
                    : null;

                return [
                    'section_id' => (int) $row->section_id,
                    'section_name' => $sectionNames[(int) $row->section_id] ?? null,
                    'subject_id' => (int) $row->subject_id,
                    'subject_name' => $subjectNames[(int) $row->subject_id] ?? null,
                    'teacher_id' => $tid,
                    'teacher_name' => $tid ? ($teacherNames[$tid] ?? null) : null,
                    'teacher_employment_type' => $empType,
                    'teacher_is_regular' => $isRegular,
                    'room_id' => $row->room_id ? (int) $row->room_id : null,
                    'room_name' => $row->room_id ? ($roomNames[(int) $row->room_id] ?? null) : null,
                    'day_of_week' => $row->day_of_week,
                    'start_time' => substr((string) $row->start_time, 0, 5),
                    'end_time' => substr((string) $row->end_time, 0, 5),
                    'status' => $row->status ?: 'valid',
                    'conflict_reasons' => $conflictReasons,
                ];
            })
            ->toArray();

        $meta = [];
        if (! empty($schedule->meta)) {
            $decoded = json_decode($schedule->meta, true);
            $meta = is_array($decoded) ? $decoded : [];
        }

        return [
            'id' => (int) $schedule->id,
            'name' => $schedule->name,
            'version' => (int) $schedule->version,
            'score' => (float) $schedule->score,
            'term_id' => $schedule->term_id ? (int) $schedule->term_id : null,
            'meta' => $meta,
            'sessions' => $sessions,
        ];
    }
}
