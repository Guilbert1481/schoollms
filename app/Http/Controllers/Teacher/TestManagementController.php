<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Services\Tests\TestManagementService;
use App\Support\EducationLevels;
use Illuminate\Http\Request;

/**
 * Teacher → Tests → Test Management: every test THIS teacher created in the
 * Test Builder, behind the education-level tabs pattern scoped to the levels
 * the teacher is actually assigned to.
 */
class TestManagementController extends Controller
{
    public function __construct(private TestManagementService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;

        $tests = $this->service->testsFor((int) $user->id, $schoolId);
        $levels = $this->service->assignedRoots((int) $user->id, $schoolId, $tests);

        // ?level= — Student Ledgers convention (null/'all' | root id).
        $levelParam = $request->query('level');
        $showAll = $levelParam === null || $levelParam === '' || strtolower((string) $levelParam) === 'all';
        $activeLevelId = $showAll ? 0 : (int) $levelParam;

        $activeLevel = $levels->firstWhere('id', $activeLevelId);
        $singleLevel = $levels->count() === 1 ? $levels->first() : null;
        $effectiveLevel = $showAll ? $singleLevel : $activeLevel;
        $activeLevelIsBasic = $effectiveLevel && EducationLevels::isBasic($effectiveLevel->name);

        // Restrict to the active tab, then layer on the dropdown filters.
        $scoped = $showAll
            ? $tests
            : $tests->filter(fn ($t) => (int) ($t->root ?? 0) === $activeLevelId)->values();

        // Program column/filter — higher-ed tabs only (house convention).
        $showProgram = ! $showAll && ! $activeLevelIsBasic && $activeLevelId > 0;

        // Filter options are built from the teacher's own (tab-scoped) tests,
        // so a dropdown can never offer another teacher's data.
        $levelOptions = $this->service->levelOptions($schoolId, $scoped);
        $subjectOptions = $scoped->filter(fn ($t) => $t->subject_id)
            ->unique('subject_id')->sortBy('subject_name')
            ->mapWithKeys(fn ($t) => [$t->subject_id => $t->subject_name])->all();
        $sectionOptions = $scoped->filter(fn ($t) => $t->section_id)
            ->unique('section_id')->sortBy('section_name')
            ->mapWithKeys(fn ($t) => [$t->section_id => $t->section_name])->all();
        $programOptions = ! $showProgram ? [] : $scoped->filter(fn ($t) => $t->program_id)
            ->unique('program_id')->sortBy('program_label')
            ->mapWithKeys(fn ($t) => [$t->program_id => $t->program_label])->all();

        $levelId = $request->integer('level_id') ?: null;
        $subjectId = $request->integer('subject_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;
        $programId = $request->integer('program_id') ?: null;

        $rows = $scoped
            ->when($levelId, fn ($c) => $c->filter(fn ($t) => in_array($levelId, $t->level_ids, true)))
            ->when($subjectId, fn ($c) => $c->filter(fn ($t) => (int) $t->subject_id === $subjectId))
            ->when($sectionId, fn ($c) => $c->filter(fn ($t) => (int) $t->section_id === $sectionId))
            ->when($programId, fn ($c) => $c->filter(fn ($t) => (int) $t->program_id === $programId))
            ->map(fn ($t) => $this->displayRow($t))
            ->values();

        return view('teacher.tests.testManagement', [
            'columns' => $this->columnsFor($showAll, $activeLevelIsBasic, $showProgram),
            'rows' => $rows,
            'levels' => $levels,
            'activeLevelId' => $activeLevelId,
            'showAll' => $showAll,
            'activeLevelIsBasic' => $activeLevelIsBasic,
            'showProgram' => $showProgram,
            'levelOptions' => $levelOptions,
            'subjectOptions' => $subjectOptions,
            'sectionOptions' => $sectionOptions,
            'programOptions' => $programOptions,
            'levelId' => $levelId,
            'subjectId' => $subjectId,
            'sectionId' => $sectionId,
            'programId' => $programId,
        ]);
    }

    public function publish(Test $test)
    {
        // BelongsToSchool already 404s another school's test at binding time;
        // this guard is ownership WITHIN the school — only the author publishes.
        abort_unless((int) $test->teacher_id === (int) auth()->id(), 403);

        $test->update(['status' => 'published']);

        return redirect()
            ->route('teacher.tests.management')
            ->with('success', 'Test published successfully.');
    }

    /**
     * Level-aware columns: the level column reads "Grade Level" / "Year Level"
     * on a specific tab, and Program appears on higher-ed tabs only.
     *
     * @return array<int, array<string, mixed>>
     */
    private function columnsFor(bool $showAll, bool $isBasic, bool $showProgram): array
    {
        $levelHeader = $showAll ? 'Level' : ($isBasic ? 'Grade Level' : 'Year Level');

        return array_values(array_filter([
            ['key' => 'title', 'label' => 'Title', 'width' => '200px'],
            ['key' => 'level_label', 'label' => $levelHeader, 'width' => '120px'],
            $showProgram ? ['key' => 'program_label', 'label' => 'Program', 'width' => '110px'] : null,
            ['key' => 'subject_name', 'label' => 'Subject', 'width' => '160px'],
            ['key' => 'section_name', 'label' => 'Section', 'width' => '120px'],
            ['key' => 'items', 'label' => 'Items', 'width' => '70px'],
            ['key' => 'mode_label', 'label' => 'Mode', 'width' => '90px'],
            ['key' => 'schedule_label', 'label' => 'Schedule', 'width' => '190px'],
            ['key' => 'created_label', 'label' => 'Created', 'width' => '110px'],
            ['key' => 'status_pill', 'label' => 'Status', 'width' => '110px', 'raw' => true],
            ['key' => 'row_actions', 'label' => 'Actions', 'width' => '110px', 'raw' => true],
        ]));
    }

    /**
     * One table row. The two raw columns build their HTML here so every
     * user-supplied value passes through e() (SECURITY_PRINCIPLES §5).
     *
     * @return array<string, mixed>
     */
    private function displayRow(object $t): array
    {
        $pill = $t->status === 'published'
            ? '<span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Published</span>'
            : ($t->status === 'draft'
                ? '<span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Draft</span>'
                : '<span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-600">'.e(ucfirst((string) $t->status)).'</span>');

        $actions = '<div class="flex items-center gap-1">'
            .'<a href="'.e(route('teacher.tests.print-hub', $t->id)).'" title="Preview" target="_blank" rel="noopener"'
            .' class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100">'
            .'<i data-lucide="eye" class="h-4 w-4"></i></a>'
            .'<a href="'.e(route('teacher.tests.edit', $t->id)).'" title="Edit"'
            .' class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">'
            .'<i data-lucide="pencil" class="h-4 w-4"></i></a>';

        // Online tests can have essay answers to score by hand → grading screen.
        if (($t->mode ?? null) === 'online') {
            $actions .= '<a href="'.e(route('teacher.tests.grade', $t->id)).'" title="Grade essays"'
                .' class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600 hover:bg-violet-100">'
                .'<i data-lucide="clipboard-check" class="h-4 w-4"></i></a>';

            // "Play as Game" — game-delivery settings. Cyan when a mode is live.
            $gameNames = ['speed_dash' => 'Quiz Speed Dash', 'snake_and_ladder' => 'Quiz Snakes & Ladders'];
            $gameName = $gameNames[$t->play_mode ?? 'standard'] ?? null;
            $gameOn = $gameName !== null;
            $actions .= '<a href="'.e(route('teacher.tests.game', $t->id)).'"'
                .' title="'.($gameOn ? $gameName.' is ON' : 'Play as Game').'"'
                .' class="inline-flex h-8 w-8 items-center justify-center rounded-lg '
                .($gameOn ? 'bg-cyan-600 text-white hover:bg-cyan-700' : 'bg-cyan-50 text-cyan-600 hover:bg-cyan-100').'">'
                .'<i data-lucide="gamepad-2" class="h-4 w-4"></i></a>';
        }

        if ($t->status === 'draft') {
            $actions .= '<form method="POST" action="'.e(route('teacher.tests.publish', $t->id)).'" class="m-0 inline-flex"'
                .' onsubmit="return confirm(\'Publish this test?\')">'
                .csrf_field()
                .'<button type="submit" title="Publish"'
                .' class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100">'
                .'<i data-lucide="send" class="h-4 w-4"></i></button></form>';
        }

        $actions .= '</div>';

        return [
            'id' => $t->id,
            'title' => $t->title,
            'level_label' => $t->level_label,
            'program_label' => $t->program_label,
            'subject_name' => $t->subject_name,
            'section_name' => $t->section_name,
            'items' => $t->items,
            'mode_label' => $t->mode_label,
            'schedule_label' => $t->schedule_label,
            'created_label' => $t->created_label,
            'status_pill' => $pill,
            'row_actions' => $actions,
        ];
    }
}
