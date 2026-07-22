<?php

namespace App\Http\Controllers\Tools\Games;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gamified Quiz catalog controller.
 *
 * Lists the available game templates and serves an individual game
 * placeholder page. Each game is a separate Blade partial under
 * resources/views/tools/games/games/{slug}.blade.php so they can be
 * fleshed out independently without touching the catalog.
 */
class GamesController extends Controller
{
    /**
     * The catalog of supported game templates.
     * Adding a new game = add an entry here and a matching partial.
     */
    public const GAMES = [
        ['slug' => 'filler',               'title' => 'Filler',                         'icon' => 'languages',      'color' => 'from-cyan-700 via-sky-800 to-blue-900',        'description' => "Fill in each verb's past simple and past participle — one at a time, in short rounds you can replay for mastery."],
        ['slug' => 'millionaire',          'title' => 'Who Wants to Be a Millionaire?', 'icon' => 'crown',          'color' => 'from-amber-700 via-orange-800 to-red-900',     'description' => 'Climb the ladder of escalating questions with lifelines and a dramatic reveal.'],
        ['slug' => 'speed-dash',           'title' => 'The Endless Runner / Speed Dash','icon' => 'zap',            'color' => 'from-cyan-700 via-sky-800 to-blue-900',        'description' => 'Sprint through obstacles by answering correctly under increasing time pressure.'],
        ['slug' => 'tower-defense',        'title' => 'Tower Defense / Base Builder',   'icon' => 'shield',         'color' => 'from-emerald-700 via-teal-800 to-cyan-900',    'description' => 'Defend the base by answering questions to deploy towers and counter waves.'],
        ['slug' => 'detective',            'title' => 'Detective Mystery Solver',       'icon' => 'search',         'color' => 'from-slate-700 via-zinc-700 to-stone-800',     'description' => 'Gather clues and crack the case by deducing the right answer.'],
        ['slug' => 'card-battler',         'title' => 'Card Battler',                   'icon' => 'layers',         'color' => 'from-fuchsia-700 via-purple-800 to-indigo-900','description' => 'Battle opponents by playing answer cards to deal damage and unlock combos.'],
        ['slug' => 'crossword',            'title' => 'Crossword Puzzle',               'icon' => 'grid-3x3',       'color' => 'from-blue-700 via-sky-800 to-indigo-900',      'description' => 'Solve themed crossword puzzles built from your subject vocabulary.'],
        ['slug' => 'word-search',          'title' => 'Word Search',                    'icon' => 'search-code',    'color' => 'from-lime-700 via-emerald-800 to-teal-900',    'description' => 'Find hidden subject terms in a randomized letter grid.'],
        ['slug' => 'cryptogram',           'title' => 'Cryptogram',                     'icon' => 'lock',           'color' => 'from-violet-700 via-purple-800 to-fuchsia-900','description' => 'Decode encrypted quotes or facts to reveal the answer.'],
        ['slug' => 'anagrams',             'title' => 'Scrambled Words / Anagrams',     'icon' => 'shuffle',        'color' => 'from-rose-700 via-pink-800 to-fuchsia-900',    'description' => 'Unscramble jumbled letters back into the correct keyword.'],
        ['slug' => 'memory-match',         'title' => 'Memory Match Tiles',             'icon' => 'square-stack',   'color' => 'from-pink-700 via-rose-800 to-red-900',        'description' => 'Match question tiles with the correct answer pairs from memory.'],
        ['slug' => 'hangman',              'title' => 'Hangman',                        'icon' => 'spell-check',    'color' => 'from-stone-700 via-zinc-800 to-slate-900',     'description' => 'Guess letters one at a time before the figure is fully drawn.'],
        ['slug' => 'category-sorter',      'title' => 'Drag and Drop Category Sorter',  'icon' => 'columns-3',      'color' => 'from-orange-700 via-amber-800 to-yellow-900',  'description' => 'Drag items into the correct category bucket to score points.'],
        ['slug' => 'timeline-sequence',    'title' => 'Timeline / Sequence Ordering',   'icon' => 'list-ordered',   'color' => 'from-indigo-700 via-blue-800 to-sky-900',      'description' => 'Arrange events or steps into the correct chronological order.'],
        ['slug' => 'labeling-diagram',     'title' => 'Labeling Diagram Map',           'icon' => 'map-pin',        'color' => 'from-teal-700 via-cyan-800 to-sky-900',        'description' => 'Drop labels onto the right hotspots of a diagram or map.'],
        ['slug' => 'fill-blanks',          'title' => 'Fill-in-the-Blanks (Drag-to-Text)', 'icon' => 'pen-tool',    'color' => 'from-emerald-700 via-green-800 to-lime-900',   'description' => 'Drag words into the right slots of a sentence or paragraph.'],
        ['slug' => 'connector-lines',      'title' => 'Relational Connector Lines',     'icon' => 'git-fork',       'color' => 'from-purple-700 via-violet-800 to-indigo-900', 'description' => 'Connect prompts and answers by drawing matching lines.'],
        ['slug' => 'tug-of-war',           'title' => 'Tug-of-War (The Momentum Battle)','icon' => 'swords',        'color' => 'from-red-700 via-rose-800 to-pink-900',        'description' => 'Two-side momentum battle where each correct answer pulls the rope.'],
        ['slug' => 'snake-and-ladder',     'title' => 'Snake and Ladder',               'icon' => 'dice-5',         'color' => 'from-green-700 via-emerald-800 to-teal-900',   'description' => 'Roll the dice and answer to climb ladders, miss to slide down snakes.'],
    ];

    public function index()
    {
        return view('tools.games.index', [
            'games' => self::GAMES,
        ]);
    }

    public function play(Request $request, string $slug)
    {
        $game = collect(self::GAMES)->firstWhere('slug', $slug);
        abort_if($game === null, 404);

        $user     = auth()->user();
        $schoolId = (int) ($user?->school_id ?? 0);

        return view('tools.games.play', [
            'game'  => $game,
            'games' => self::GAMES,
            // ?embed=1 renders on the bare fullscreen layout (no sidebar/header)
            // for the catalog's distraction-free game overlay.
            'embed' => $request->boolean('embed'),
            'ctx'   => $this->gameContext($user, $schoolId),
        ]);
    }

    /**
     * Everything the in-game hamburger needs: the cascading content bank,
     * year-level vocabulary (UI says "year/grade level" — ADR-0006), the
     * user's auto-captured level, the teacher's quiz-mode settings, or the
     * student's quiz-mode lock.
     */
    private function gameContext($user, int $schoolId): array
    {
        $isTeacher = strtolower((string) ($user->role ?? '')) === 'teacher';

        $bank = [
            'subjects' => DB::table('subjects')->where('school_id', $schoolId)->where('is_active', 1)
                ->orderBy('name')->get(['id', 'name'])->all(),
            'topics' => DB::table('topics')->where('school_id', $schoolId)->where('is_active', 1)
                ->orderBy('sequence')->get(['id', 'subject_id', 'name'])->all(),
            'lessons' => DB::table('lessons')->where('school_id', $schoolId)->where('is_active', 1)
                ->orderBy('sequence')->get(['id', 'subject_id', 'topic_id', 'name'])->all(),
            'competencies' => DB::table('competencies')->where('school_id', $schoolId)->where('is_active', 1)
                ->orderBy('sequence')->get(['id', 'subject_id', 'topic_id', 'lesson_id', 'name'])->all(),
        ];

        $levels = DB::table('academic_levels')->where('school_id', $schoolId)
            ->whereIn('type', ['basic', 'higher'])
            ->orderBy('type')->orderBy('sequence_order')
            ->get(['id', 'name', 'type'])->all();

        // ---- Auto-capture the user's year level (ADR-0006 bridge) ----
        $autoLevelId = null;
        $segment     = null; // 'basic' | 'higher' — drives 3 vs 4 grading terms

        if ($isTeacher) {
            // Teacher: the year levels of the sections they teach.
            $yearLevels = DB::table('classes as c')
                ->join('sections as s', 's.id', '=', 'c.section_id')
                ->where('c.school_id', $schoolId)
                ->where(fn ($q) => $q->where('c.teacher_id', $user->id)
                    ->orWhereIn('c.id', fn ($sub) => $sub->select('class_id')->from('class_teacher')->where('teacher_id', $user->id)))
                ->distinct()->pluck('s.year_level')->filter()->values();

            if ($yearLevels->count() === 1) {
                $autoLevelId = $this->levelIdFor($schoolId, null, (int) $yearLevels[0]);
            }
            // Segment: teachers may straddle both; default to higher (4 terms).
            $segment = 'higher';
        } else {
            // Student: latest enrollment carries education_level + year_level.
            $student = DB::table('students')->where('user_id', $user->id)->first();
            $enr = $student ? DB::table('student_enrollments')
                ->where('student_id', $student->id)
                ->orderByDesc('id')->first() : null;

            if ($enr) {
                $autoLevelId = $this->levelIdFor($schoolId, $enr->education_level, (int) $enr->year_level);
                $segment = in_array($enr->education_level, ['kinder', 'elementary', 'junior_high', 'senior_high'], true)
                    ? 'basic' : 'higher';
            }
        }

        // ---- Quiz mode ----
        $quiz = $isTeacher
            ? DB::table('game_quiz_settings')->where('teacher_id', $user->id)->first()
            : null;

        $lock = null;
        if (! $isTeacher) {
            $lock = $this->resolveStudentLock($user, $schoolId);
        }

        return [
            'role'        => $isTeacher ? 'teacher' : 'student',
            'bank'        => $bank,
            'levels'      => $levels,
            'autoLevelId' => $autoLevelId,
            'termCount'   => $segment === 'basic' ? 3 : 4,
            'quiz'        => $quiz,
            'lock'        => $lock,
        ];
    }

    /** education_level + year_level -> academic_levels row id (ADR-0006). */
    private function levelIdFor(int $schoolId, ?string $educationLevel, int $yearLevel): ?int
    {
        if ($yearLevel < 1 && $educationLevel !== 'kinder') {
            return null;
        }

        $isBasic = $educationLevel === null
            || in_array($educationLevel, ['kinder', 'elementary', 'junior_high', 'senior_high'], true);

        $name = $educationLevel === 'kinder'
            ? 'Kinder'
            : ($isBasic ? 'Grade '.$yearLevel : 'Year '.$yearLevel);

        return DB::table('academic_levels')
            ->where('school_id', $schoolId)
            ->where('type', $isBasic ? 'basic' : 'higher')
            ->where('name', $name)
            ->value('id');
    }

    /**
     * If any teacher of the student's classes has Quiz Mode ON, the most
     * recently updated setting wins and locks the student's game scope.
     */
    private function resolveStudentLock($user, int $schoolId): ?array
    {
        $student = DB::table('students')->where('user_id', $user->id)->first();
        if (! $student) {
            return null;
        }

        $teacherIds = DB::table('class_student as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_model_id')
            ->where('cs.student_id', $student->id)
            ->where('c.school_id', $schoolId)
            ->pluck('c.teacher_id')
            ->merge(
                DB::table('class_student as cs')
                    ->join('class_teacher as ct', 'ct.class_id', '=', 'cs.class_model_id')
                    ->where('cs.student_id', $student->id)
                    ->pluck('ct.teacher_id')
            )
            ->filter()->unique()->values();

        if ($teacherIds->isEmpty()) {
            return null;
        }

        $setting = DB::table('game_quiz_settings')
            ->whereIn('teacher_id', $teacherIds)
            ->where('is_on', 1)
            ->orderByDesc('updated_at')
            ->first();

        if (! $setting) {
            return null;
        }

        $teacher = DB::table('users')->where('id', $setting->teacher_id)->first();

        return [
            'teacher_name'      => trim(($teacher->first_name ?? '').' '.($teacher->last_name ?? '')) ?: 'Your teacher',
            'term'              => $setting->term,
            'academic_level_id' => $setting->academic_level_id,
            'subject_id'        => $setting->subject_id,
            'topic_id'          => $setting->topic_id,
            'lesson_id'         => $setting->lesson_id,
            'competency_id'     => $setting->competency_id,
        ];
    }

    /** Teacher-only: save Quiz Mode toggle + content selection. */
    public function saveQuizMode(Request $request)
    {
        $user = auth()->user();
        abort_unless(strtolower((string) $user->role) === 'teacher', 403);

        $data = $request->validate([
            'is_on'             => ['required', 'boolean'],
            'term'              => ['nullable', 'integer', 'min:1', 'max:4'],
            'academic_level_id' => ['nullable', 'integer', 'exists:academic_levels,id'],
            'subject_id'        => ['nullable', 'integer', 'exists:subjects,id'],
            'topic_id'          => ['nullable', 'integer', 'exists:topics,id'],
            'lesson_id'         => ['nullable', 'integer', 'exists:lessons,id'],
            'competency_id'     => ['nullable', 'integer', 'exists:competencies,id'],
        ]);

        DB::table('game_quiz_settings')->updateOrInsert(
            ['teacher_id' => $user->id],
            $data + [
                'school_id'  => (int) $user->school_id,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Question supply for the games (JSON).
     *
     * Serves random question-bank items of one type, school-scoped, with the
     * answers included — games grade CLIENT-SIDE, so this endpoint is for
     * practice play against the question bank, not for graded exams.
     */
    public function questions(Request $request)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $type = (string) $request->query('type');
        abort_unless(in_array($type, ['mcq', 'identification'], true), 422);

        // The MCQ builder historically wrote both spellings.
        $types = $type === 'mcq' ? ['mcq', 'multiple_choice'] : ['identification'];

        $limit = min(max((int) $request->query('limit', 15), 1), 30);

        // Difficulty tier. The question bank stores 'average' | 'advanced' (see
        // McqController / AiQuestionController); legacy rows may hold 'easy'/etc.
        // 'average' / 'advanced' filter to that tier; 'mixed' climbs average-first
        // then advanced (~50/50, backfilling from whichever tier has supply);
        // anything else (incl. absent, e.g. the Hangman game) = no tier constraint.
        $difficulty = (string) $request->query('difficulty', '');

        // A fresh base query per pull — query builders are single-use. Scope
        // filters from the hamburger: subject > topic > lesson > competency (each
        // narrower), plus the auto-captured year level. The grading TERM is
        // display/lock context only (ADR-0006) — questions are not term-tagged.
        $base = fn () => DB::table('questions')
            ->where('school_id', $schoolId)
            ->whereIn('question_type', $types)
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', (int) $request->query('subject_id')))
            ->when($request->filled('topic_id'), fn ($q) => $q->where('topic_id', (int) $request->query('topic_id')))
            ->when($request->filled('lesson_id'), fn ($q) => $q->where('lesson_id', (int) $request->query('lesson_id')))
            ->when($request->filled('competency_id'), fn ($q) => $q->where('competency_id', (int) $request->query('competency_id')))
            ->when($request->filled('academic_level_id'), fn ($q) => $q->where('academic_level_id', (int) $request->query('academic_level_id')));

        // Pull up to $want *valid* questions of an optional difficulty tier.
        $pull = function (?string $tier, int $want) use ($base, $type) {
            if ($want <= 0) {
                return collect();
            }

            $rows = $base()
                ->when($tier !== null, fn ($q) => $q->where('difficulty', $tier))
                ->inRandomOrder()
                ->limit($want * 2)   // headroom: malformed rows are filtered below
                ->get(['id', 'question_text', 'explanation', 'difficulty']);

            return $this->buildQuestions($rows, $type)->take($want)->values();
        };

        if ($difficulty === 'average' || $difficulty === 'advanced') {
            $questions = $pull($difficulty, $limit);
        } elseif ($difficulty === 'mixed') {
            // Average block first, advanced fills the rest — then top up from
            // whichever tier still has leftovers so a thin tier never short-changes
            // the run (e.g. no 'advanced' authored yet → falls back to average).
            $avgPool = $pull('average', $limit);
            $advPool = $pull('advanced', $limit);
            $half    = (int) ceil($limit / 2);

            $avg = $avgPool->take($half);
            $adv = $advPool->take($limit - $avg->count());
            $questions = $avg->concat($adv)->values();

            if ($questions->count() < $limit) {
                $usedIds = $questions->pluck('id')->all();
                $leftover = $avgPool->concat($advPool)
                    ->reject(fn ($q) => in_array($q['id'], $usedIds, true));
                $questions = $questions->concat($leftover)->take($limit)->values();
            }
        } else {
            $questions = $pull(null, $limit);
        }

        return response()->json(['questions' => $questions]);
    }

    /**
     * Shape raw question rows into the game payload, dropping malformed items
     * (identification with no stored answer; MCQ without >= 2 options and exactly
     * one correct). MCQ choices are shuffled and the answer index re-derived.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @return \Illuminate\Support\Collection
     */
    private function buildQuestions($rows, string $type)
    {
        $choicesByQuestion = DB::table('choices')
            ->whereIn('question_id', $rows->pluck('id'))
            ->get(['question_id', 'choice_text', 'is_correct'])
            ->groupBy('question_id');

        return $rows->map(function ($row) use ($type, $choicesByQuestion) {
            $choices = ($choicesByQuestion[$row->id] ?? collect())->values();

            if ($type === 'identification') {
                $answer = trim((string) optional($choices->firstWhere('is_correct', 1))->choice_text);
                if ($answer === '') {
                    return null; // malformed: no stored answer
                }

                return [
                    'id'          => $row->id,
                    'question'    => $row->question_text,
                    'answer'      => $answer,
                    'explanation' => $row->explanation,
                ];
            }

            // MCQ: need >= 2 options and exactly one correct.
            if ($choices->count() < 2 || (int) $choices->where('is_correct', 1)->count() !== 1) {
                return null;
            }

            $shuffled = $choices->shuffle()->values();

            return [
                'id'          => $row->id,
                'question'    => $row->question_text,
                'choices'     => $shuffled->pluck('choice_text')->all(),
                'answer'      => $shuffled->search(fn ($c) => (int) $c->is_correct === 1),
                'explanation' => $row->explanation,
            ];
        })->filter()->values();
    }
}
