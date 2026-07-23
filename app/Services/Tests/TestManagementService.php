<?php

namespace App\Services\Tests;

use App\Models\AcademicLevel;
use App\Support\EducationLevels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read model for the teacher's Test Management page.
 *
 * Everything here is derived from ONE teacher's own data — the tests they
 * authored in the Test Builder and the sections they teach or advise — so the
 * level tabs, the filter options and the table rows can never surface another
 * teacher's (or another school's) work.
 *
 * Level resolution per test, most-authoritative first:
 *   1. class → section → program → education_node → root (higher ed);
 *   2. class → section → term.education_level = 'basic_ed' → the Basic root;
 *   3. the builder's required academic_levels tags → LevelTreeResolver map.
 */
class TestManagementService
{
    public function __construct(private TeacherLevelScope $levelScope) {}

    /**
     * The teacher's own tests, newest first, flattened to the display fields
     * the management table needs (root, level label, subject, section, …).
     * Plain ->id/->title/… row objects.
     */
    public function testsFor(int $userId, int $schoolId): Collection
    {
        $roots = EducationLevels::offeredRoots();
        $basicRootId = $this->levelScope->basicRootId($roots);
        $nodeRoot = EducationLevels::nodeRootMap();
        $levelToRoot = $this->levelScope->levelToRootMap($schoolId, $roots);

        $levelNames = AcademicLevel::where('school_id', $schoolId)
            ->orderBy('sequence_order')
            ->pluck('name', 'id');

        $termLevels = DB::table('terms')
            ->where('school_id', $schoolId)
            ->pluck('education_level', 'id');

        $tests = DB::table('tests')
            ->where('school_id', $schoolId)
            ->where('teacher_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'title', 'status', 'subject_id', 'class_id', 'academic_levels', 'created_at']);

        $testIds = $tests->pluck('id');

        $subjectNames = DB::table('subjects')
            ->where('school_id', $schoolId)
            ->pluck('name', 'id');

        // class → section (+ its program) in one lookup, keyed by class id.
        $classInfo = DB::table('classes as c')
            ->join('sections as s', 's.id', '=', 'c.section_id')
            ->leftJoin('programs as p', 'p.id', '=', 's.program_id')
            ->where('c.school_id', $schoolId)
            ->whereIn('c.id', $tests->pluck('class_id')->filter()->unique())
            ->get([
                'c.id as class_id', 's.id as section_id', 's.name as section_name', 's.term_id',
                'p.id as program_id', 'p.code as program_code', 'p.name as program_name', 'p.education_node_id',
            ])
            ->keyBy('class_id');

        $settings = DB::table('test_settings')
            ->whereIn('test_id', $testIds)
            ->get()
            ->keyBy('test_id');

        $itemCounts = DB::table('test_questions')
            ->whereIn('test_id', $testIds)
            ->selectRaw('test_id, COUNT(*) as items')
            ->groupBy('test_id')
            ->pluck('items', 'test_id');

        return $tests->map(function ($test) use (
            $basicRootId, $nodeRoot, $levelToRoot, $levelNames, $termLevels,
            $subjectNames, $classInfo, $settings, $itemCounts
        ) {
            $class = $test->class_id ? ($classInfo[$test->class_id] ?? null) : null;
            $setting = $settings[$test->id] ?? null;

            $levelIds = collect(json_decode((string) $test->academic_levels, true) ?: [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            $root = null;
            if ($class?->education_node_id) {
                $root = $nodeRoot[$class->education_node_id] ?? null;
            }
            if (! $root && $class && ($termLevels[$class->term_id] ?? null) === 'basic_ed') {
                $root = $basicRootId ?: null;
            }
            if (! $root) {
                foreach ($levelIds as $id) {
                    if (isset($levelToRoot[$id])) {
                        $root = $levelToRoot[$id];
                        break;
                    }
                }
            }

            return (object) [
                'id' => (int) $test->id,
                'title' => (string) $test->title,
                'status' => (string) $test->status,
                'root' => $root ? (int) $root : null,
                'level_ids' => $levelIds,
                'level_label' => collect($levelIds)
                    ->map(fn ($id) => $levelNames[$id] ?? null)
                    ->filter()
                    ->implode(', ') ?: '—',
                'subject_id' => $test->subject_id ? (int) $test->subject_id : null,
                'subject_name' => (string) ($subjectNames[$test->subject_id] ?? '—'),
                'section_id' => $class ? (int) $class->section_id : null,
                'section_name' => (string) ($class->section_name ?? '—'),
                'program_id' => $class?->program_id ? (int) $class->program_id : null,
                'program_label' => (string) ($class?->program_id ? ($class->program_code ?: $class->program_name) : '—'),
                'items' => (int) ($itemCounts[$test->id] ?? 0),
                'mode' => $setting?->mode,
                'mode_label' => $this->modeLabel($setting?->mode),
                'play_mode' => $setting->play_mode ?? 'standard',
                'schedule_label' => $this->scheduleLabel($setting),
                'created_label' => $test->created_at ? Carbon::parse($test->created_at)->format('M j, Y') : '—',
            ];
        });
    }

    /**
     * The level tabs for this teacher: offered roots (tree order) the teacher
     * either teaches/advises under or has authored a test in — never the whole
     * school's catalogue. Same ->id/->name shape <x-table.level-tabs> expects.
     *
     * @param  Collection  $tests  output of testsFor()
     */
    public function assignedRoots(int $userId, int $schoolId, Collection $tests): Collection
    {
        $roots = EducationLevels::offeredRoots();

        $present = $this->levelScope->sectionRoots($userId, $schoolId, $roots)
            ->merge($tests->pluck('root')->filter())
            ->map(fn ($id) => (int) $id)
            ->unique();

        return $roots->filter(fn ($r) => $present->contains((int) $r->id))->values();
    }

    /**
     * Grade/Year filter options: the school's academic-level vocabulary in
     * sequence order, limited to levels actually tagged on the given tests.
     *
     * @param  Collection  $tests  output of testsFor()
     * @return array<int, string>
     */
    public function levelOptions(int $schoolId, Collection $tests): array
    {
        $present = $tests->flatMap(fn ($t) => $t->level_ids)->unique();

        return AcademicLevel::where('school_id', $schoolId)
            ->orderBy('sequence_order')
            ->pluck('name', 'id')
            ->filter(fn ($name, $id) => $present->contains((int) $id))
            ->all();
    }

    private function modeLabel(?string $mode): string
    {
        return match ($mode) {
            'online' => 'Online',
            'f2f' => 'F2F',
            null, '' => '—',
            default => ucfirst($mode),
        };
    }

    /** Availability window — only online tests carry one (F2F prints instead). */
    private function scheduleLabel(?\stdClass $setting): string
    {
        if (! $setting || ! $setting->start_at) {
            return '—';
        }

        $label = Carbon::parse($setting->start_at)->format('M j, Y g:i A');
        if ($setting->end_at) {
            $label .= ' – '.Carbon::parse($setting->end_at)->format('M j, Y g:i A');
        }

        return $label;
    }
}
