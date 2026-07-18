<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Questions\QuestionBankService;
use App\Support\EducationLevels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Teacher → Tests → Question Bank: every question THIS teacher authored in the
 * question builders, behind the education-level tabs pattern scoped to the
 * levels the teacher is actually assigned to. Read + guarded delete only —
 * authoring stays in the builders (Create Questions flow).
 */
class QuestionBankController extends Controller
{
    public function __construct(private QuestionBankService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;

        $questions = $this->service->questionsFor((int) $user->id, $schoolId);
        $levels = $this->service->assignedRoots((int) $user->id, $schoolId, $questions);

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
            ? $questions
            : $questions->filter(fn ($q) => (int) ($q->root ?? 0) === $activeLevelId)->values();

        // Filter options are built from the teacher's own (tab-scoped) bank,
        // so a dropdown can never offer another teacher's data.
        $levelOptions = $this->service->levelOptions($schoolId, $scoped);
        $subjectOptions = $scoped->filter(fn ($q) => $q->subject_id)
            ->unique('subject_id')->sortBy('subject_name')
            ->mapWithKeys(fn ($q) => [$q->subject_id => $q->subject_name])->all();
        $topicOptions = $scoped->filter(fn ($q) => $q->topic_id)
            ->unique('topic_id')->sortBy('topic_name')
            ->mapWithKeys(fn ($q) => [$q->topic_id => $q->topic_name])->all();
        $typeOptions = $scoped->pluck('question_type')->filter()->unique()->sort()
            ->mapWithKeys(fn ($t) => [$t => QuestionBankService::typeLabel($t)])->all();
        $difficultyOptions = $scoped->pluck('difficulty')->filter()->unique()->sort()
            ->mapWithKeys(fn ($d) => [$d => ucfirst($d)])->all();

        $levelId = $request->integer('level_id') ?: null;
        $subjectId = $request->integer('subject_id') ?: null;
        $topicId = $request->integer('topic_id') ?: null;
        $type = (string) $request->query('type', '') ?: null;
        $difficulty = (string) $request->query('difficulty', '') ?: null;

        $rows = $scoped
            ->when($levelId, fn ($c) => $c->filter(fn ($q) => $q->level_id === $levelId))
            ->when($subjectId, fn ($c) => $c->filter(fn ($q) => (int) $q->subject_id === $subjectId))
            ->when($topicId, fn ($c) => $c->filter(fn ($q) => (int) $q->topic_id === $topicId))
            ->when($type, fn ($c) => $c->filter(fn ($q) => $q->question_type === $type))
            ->when($difficulty, fn ($c) => $c->filter(fn ($q) => $q->difficulty === $difficulty))
            ->map(fn ($q) => $this->displayRow($q))
            ->values();

        return view('teacher.question-bank.index', [
            'columns' => $this->columnsFor($showAll, $activeLevelIsBasic),
            'rows' => $rows,
            'levels' => $levels,
            'activeLevelId' => $activeLevelId,
            'showAll' => $showAll,
            'activeLevelIsBasic' => $activeLevelIsBasic,
            'levelOptions' => $levelOptions,
            'subjectOptions' => $subjectOptions,
            'topicOptions' => $topicOptions,
            'typeOptions' => $typeOptions,
            'difficultyOptions' => $difficultyOptions,
            'levelId' => $levelId,
            'subjectId' => $subjectId,
            'topicId' => $topicId,
            'type' => $type,
            'difficulty' => $difficulty,
        ]);
    }

    /** Full detail for the view modal — the row's choices, answer and metadata. */
    public function show(Request $request, int $question): JsonResponse
    {
        $row = $this->ownQuestionOrAbort($request, $question);

        $names = $this->lookupNames($row);

        $choices = DB::table('choices')
            ->where('question_id', $row->id)
            ->orderBy('id')
            ->get(['choice_text', 'is_correct'])
            ->map(fn ($c) => ['text' => (string) $c->choice_text, 'correct' => (bool) $c->is_correct])
            ->values();

        return response()->json([
            'id' => (int) $row->id,
            'question_text' => (string) $row->question_text,
            'type_label' => QuestionBankService::typeLabel($row->question_type),
            'difficulty' => $row->difficulty ? ucfirst((string) $row->difficulty) : '—',
            'points' => $row->points !== null ? (int) $row->points : null,
            'keyword' => (string) ($row->keyword ?? ''),
            'explanation' => (string) ($row->explanation ?? ''),
            'level' => $names['level'],
            'subject' => $names['subject'],
            'topic' => $names['topic'],
            'lesson' => $names['lesson'],
            'competency' => $names['competency'],
            'created' => $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('M j, Y g:i A') : '—',
            'used_in' => (int) DB::table('test_questions')->where('question_id', $row->id)->distinct()->count('test_id'),
            'choices' => $choices,
        ]);
    }

    /** Delete an own, unused question (its choices go with it). */
    public function destroy(Request $request, int $question)
    {
        $row = $this->ownQuestionOrAbort($request, $question);

        // A question already placed on a test is part of what students take
        // (or took) — deleting it out from under the test is never allowed.
        $used = DB::table('test_questions')->where('question_id', $row->id)->exists();
        if ($used) {
            return back()->with('error', 'This question is used by a test and cannot be deleted.');
        }

        DB::transaction(function () use ($row) {
            DB::table('choices')->where('question_id', $row->id)->delete();
            DB::table('questions')->where('id', $row->id)->delete();
        });

        return back()->with('success', 'Question deleted from your bank.');
    }

    /**
     * Tenant + ownership guard: another school's question is a 404 (it does
     * not exist for this user); a colleague's question is a 403.
     */
    private function ownQuestionOrAbort(Request $request, int $questionId): object
    {
        $row = DB::table('questions')->where('id', $questionId)->first();

        abort_unless($row && (int) $row->school_id === (int) $request->user()->school_id, 404);
        abort_unless((int) $row->teacher_id === (int) $request->user()->id, 403);

        return $row;
    }

    /** @return array{level:string,subject:string,topic:string,lesson:string,competency:string} */
    private function lookupNames(object $row): array
    {
        $name = fn (string $table, $id) => $id
            ? (string) (DB::table($table)->where('id', $id)->value('name') ?? '—')
            : '—';

        return [
            'level' => $name('academic_levels', $row->academic_level_id),
            'subject' => $name('subjects', $row->subject_id),
            'topic' => $name('topics', $row->topic_id),
            'lesson' => $name('lessons', $row->lesson_id),
            'competency' => $name('competencies', $row->competency_id),
        ];
    }

    /**
     * Level-aware columns: the level column reads "Grade Level" / "Year Level"
     * on a specific tab (house convention shared with Test Management).
     *
     * @return array<int, array<string, mixed>>
     */
    private function columnsFor(bool $showAll, bool $isBasic): array
    {
        $levelHeader = $showAll ? 'Level' : ($isBasic ? 'Grade Level' : 'Year Level');

        return [
            ['key' => 'question_label', 'label' => 'Question', 'width' => '260px'],
            ['key' => 'type_label', 'label' => 'Type', 'width' => '120px'],
            ['key' => 'level_label', 'label' => $levelHeader, 'width' => '100px'],
            ['key' => 'subject_name', 'label' => 'Subject', 'width' => '140px'],
            ['key' => 'topic_name', 'label' => 'Topic', 'width' => '140px'],
            ['key' => 'competency_name', 'label' => 'Competency', 'width' => '160px'],
            ['key' => 'difficulty_pill', 'label' => 'Difficulty', 'width' => '90px', 'raw' => true],
            ['key' => 'used_label', 'label' => 'Used In', 'width' => '80px'],
            ['key' => 'created_label', 'label' => 'Created', 'width' => '100px'],
            ['key' => 'row_actions', 'label' => 'Actions', 'width' => '90px', 'raw' => true],
        ];
    }

    /**
     * One table row. The raw columns build their HTML here so every
     * user-supplied value passes through e() (SECURITY_PRINCIPLES §5).
     *
     * @return array<string, mixed>
     */
    private function displayRow(object $q): array
    {
        $pill = $this->difficultyPill($q->difficulty);

        $actions = '<div class="flex items-center gap-1">'
            .'<button type="button" title="View" onclick="qbView('.(int) $q->id.')"'
            .' class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">'
            .'<i data-lucide="eye" class="h-4 w-4"></i></button>';

        if ($q->used_in === 0) {
            $actions .= '<form method="POST" action="'.e(route('teacher.question_bank.destroy', $q->id)).'" class="m-0 inline-flex"'
                .' onsubmit="return confirm(\'Delete this question from your bank?\')">'
                .csrf_field()
                .method_field('DELETE')
                .'<button type="submit" title="Delete"'
                .' class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100">'
                .'<i data-lucide="trash-2" class="h-4 w-4"></i></button></form>';
        }

        $actions .= '</div>';

        return [
            'id' => $q->id,
            'question_label' => Str::limit($q->question_text, 90),
            'type_label' => $q->type_label,
            'level_label' => $q->level_label,
            'subject_name' => $q->subject_name,
            'topic_name' => $q->topic_name,
            'competency_name' => $q->competency_name,
            'difficulty_pill' => $pill,
            'used_label' => $q->used_in > 0 ? $q->used_in.' '.Str::plural('test', $q->used_in) : '—',
            'created_label' => $q->created_label,
            'row_actions' => $actions,
        ];
    }

    private function difficultyPill(?string $difficulty): string
    {
        if (! $difficulty) {
            return '<span class="text-slate-400">—</span>';
        }

        $tone = match (strtolower($difficulty)) {
            'easy' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'average', 'moderate', 'medium' => 'border-amber-200 bg-amber-50 text-amber-700',
            'hard', 'difficult' => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-slate-200 bg-slate-50 text-slate-600',
        };

        return '<span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold '.$tone.'">'
            .e(ucfirst($difficulty)).'</span>';
    }
}
