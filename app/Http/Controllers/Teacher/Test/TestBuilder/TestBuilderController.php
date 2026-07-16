<?php

namespace App\Http\Controllers\Teacher\Test\TestBuilder;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Competency;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\Topic;
use App\Services\Tests\LevelTreeResolver;
use App\Support\SubjectScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestBuilderController extends Controller
{
    public function __construct(private LevelTreeResolver $levels) {}

    /**
     * Education-structure tree + resolved academic_levels-per-node for the
     * "Assessment Levels" cascade. Shared by create/index/edit.
     */
    private function levelPickerData(): array
    {
        $schoolId = (int) auth()->user()->school_id;

        return [
            'levelTree' => $this->levels->tree(),
            'levelsByNode' => $this->levels->levelsByNode($schoolId),
        ];
    }

    /* ===============================
       PAGE LOADER
    =============================== */
    public function index($testId)
    {
        $test = Test::with(['testSources', 'testSettings'])->findOrFail($testId);
        // C1 — tenant guard: never expose a test from another school.
        abort_unless((int) $test->school_id === (int) auth()->user()->school_id, 404);

        $academicLevels = \App\Models\AcademicLevel::where('school_id', auth()->user()->school_id)->orderBy('sequence_order')->get();

        return view('teacher.tests.testBuilder', [
            'test' => $test,
            'subjects' => SubjectScope::applyTo(Subject::query())->orderBy('name')->get(),
            'academicLevels' => $academicLevels,
            'classes' => ClassModel::with(['section', 'subject'])
                ->where('teacher_id', auth()->id())
                ->get(),

        ] + $this->levelPickerData());
    }

    public function create()
    {
        $academicLevels = \App\Models\AcademicLevel::where('school_id', auth()->user()->school_id)->orderBy('sequence_order')->get();

        return view('teacher.tests.testBuilder', [
            'test' => null,
            'subjects' => SubjectScope::applyTo(Subject::query())->orderBy('name')->get(),
            'academicLevels' => $academicLevels,
            'classes' => ClassModel::with(['section', 'subject'])
                ->where('teacher_id', auth()->id())
                ->get(),

        ] + $this->levelPickerData());
    }

    public function edit(Test $test)
    {
        // C1 — tenant guard: never expose a test from another school.
        abort_unless((int) $test->school_id === (int) auth()->user()->school_id, 404);

        $test->load(['testSources', 'testSettings', 'testQuestions.question']);

        $academicLevels = \App\Models\AcademicLevel::where('school_id', auth()->user()->school_id)->orderBy('sequence_order')->get();

        return view('teacher.tests.testBuilder', [
            'test' => $test,
            'subjects' => SubjectScope::applyTo(Subject::query())->orderBy('name')->get(),
            'academicLevels' => $academicLevels,
            'classes' => ClassModel::with(['section', 'subject'])
                ->where('teacher_id', auth()->id())
                ->get(),
        ] + $this->levelPickerData());
    }

    /* ===============================
       LOAD TEST DATA (for editing existing test)
    =============================== */
    public function loadTest($testId)
    {
        $test = Test::with([
            'testSources',
            'testSettings',
            'subject',
            'topic',
            'lesson',
            'competency',
        ])->findOrFail($testId);
        // C1 — tenant guard: never expose a test from another school.
        abort_unless((int) $test->school_id === (int) auth()->user()->school_id, 404);

        // Format test sources for frontend
        $questionSettings = $test->testSources->map(function ($source) {
            return [
                'source_id' => $source->source_id,
                'source_type' => $source->source_type,
                'source_name' => $this->getSourceName($source->source_type, $source->source_id),
                'mcq' => $source->mcq_count,
                'tf' => $source->tf_count,
                'mtf' => $source->mtf_count,
                'id' => $source->identification_count,
                'match' => $source->matching_count,
                'fib' => $source->fib_count,
                'enum' => $source->enumeration_count,
                'essay' => $source->essay_count,
            ];
        });

        return response()->json([
            'success' => true,
            'test' => [
                'id' => $test->id,
                'subject_id' => $test->subject_id,
                'topic_id' => $test->topic_id,
                'lesson_id' => $test->lesson_id,
                'competency_id' => $test->competency_id,
                'difficulty' => $test->difficulty,
                'academic_level' => $test->academic_level,
                'assessment_type' => $test->assessment_type,
                'title' => $test->title,
                'status' => $test->status,
            ],
            'settings' => $test->testSettings ? [
                'timer_minutes' => $test->testSettings->timer_minutes,
                'attempts_allowed' => $test->testSettings->attempts_allowed,
                'passing_score' => $test->testSettings->passing_score,
                'show_results' => $test->testSettings->show_results,
                'show_correct_answers' => $test->testSettings->show_correct_answers,
                'shuffle_questions' => $test->testSettings->shuffle_questions,
                'shuffle_mcq_choices' => $test->testSettings->shuffle_mcq_choices,
                'start_at' => $test->testSettings->start_at,
                'end_at' => $test->testSettings->end_at,
                'mode' => $test->testSettings->mode,
                'term' => $test->testSettings->term,
            ] : null,
            'question_settings' => $questionSettings,
        ]);
    }

    /* ===============================
       GET AVAILABLE SUBJECTS BY LEVEL
       Level gates the Subject list: only subjects that actually have questions
       tagged at the selected academic_level(s) are returned, so the dropdown is
       scoped instead of dumping the school's whole subject catalogue.
    =============================== */
    public function getAvailableSubjects(Request $request)
    {
        $academicLevels = array_filter((array) $request->input('academic_levels', []));

        // Level is the source of truth — no level picked, no subjects.
        if (empty($academicLevels)) {
            return response()->json([]);
        }

        $subjects = SubjectScope::applyTo(Subject::query())
            ->whereHas('topics.questions', function ($q) use ($academicLevels) {
                $q->whereIn('academic_level_id', $academicLevels);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($subjects);
    }

    /* ===============================
       GET AVAILABLE TOPICS BY SUBJECT (Only topics w/ questions)
    =============================== */
    public function getAvailableTopics($subjectId, Request $request)
    {
        // Optional filtering
        $difficulty = $request->input('difficulty');
        $academicLevels = $request->input('academic_levels');

        $topics = Topic::where('subject_id', $subjectId)
            ->whereHas('questions', function ($q) use ($difficulty, $academicLevels) {
                if ($difficulty) {
                    $q->whereIn('difficulty', (array) $difficulty);
                }
                if ($academicLevels) {
                    $q->whereIn('academic_level_id', (array) $academicLevels);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($topics);
    }

    /* ===============================
       GET AVAILABLE LESSONS BY TOPIC (Only lessons w/ questions)
    =============================== */
    public function getAvailableLessons($topicId, Request $request)
    {
        $difficulty = $request->input('difficulty');
        $academicLevels = $request->input('academic_levels');

        $lessons = Lesson::where('topic_id', $topicId)
            ->whereHas('questions', function ($q) use ($difficulty, $academicLevels) {
                if ($difficulty) {
                    $q->whereIn('difficulty', (array) $difficulty);
                }
                if ($academicLevels) {
                    $q->whereIn('academic_level_id', (array) $academicLevels);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($lessons);
    }

    /* ===============================
       LEGACY: GET TOPICS BY SUBJECT (All, unfiltered)
    =============================== */
    public function getTopics($subjectId)
    {
        $topics = Topic::where('subject_id', $subjectId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($topics);
    }

    /* ===============================
       LEGACY: GET LESSONS BY TOPIC (All, unfiltered)
    =============================== */
    public function getLessons($topicId)
    {
        $lessons = Lesson::where('topic_id', $topicId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($lessons);
    }

    /* ===============================
       COMPETENCIES
    =============================== */
    public function getCompetencies(Request $request)
    {
        $query = Competency::query();

        if ($request->lesson_id) {
            $query->where('lesson_id', $request->lesson_id);
        } elseif ($request->topic_id) {
            $query->whereHas('lesson', function ($q) use ($request) {
                $q->where('topic_id', $request->topic_id);
            });
        } elseif ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        // Support legacy lesson name lookup
        if ($request->lesson && ! $request->lesson_id) {
            $query->where('lesson_id', function ($q) use ($request) {
                $q->select('id')
                    ->from('lessons')
                    ->where('name', $request->lesson)
                    ->limit(1);
            });
        }

        return response()->json(
            $query->orderBy('name')->pluck('name')
        );
    }

    /* ===============================
       QUESTION AVAILABILITY COUNTS
       (TOPIC‑BASED VERSION)
    =============================== */
    public function availability(Request $request)
    {
        $subjectId = $request->subject_id;
        $topicId = $request->topic_id;
        $lessonName = $request->lesson;
        $lessonId = $request->lesson_id;
        $difficulties = $request->difficulty;
        $academicLevels = $request->academic_levels;

        if (! $subjectId) {
            return response()->json([]);
        }

        /* ---------------------------------------------
           GET ALL TOPIC IDs FOR THIS SUBJECT
        --------------------------------------------- */
        $topicIds = DB::table('topics')
            ->where('subject_id', $subjectId)
            ->pluck('id');

        if ($topicIds->isEmpty()) {
            return response()->json([]);
        }

        /* ---------------------------------------------
           BASE QUESTION FILTER (NO subject_id filter)
        --------------------------------------------- */
        $base = Question::query()->whereIn('topic_id', $topicIds)
            ->when($difficulties, fn ($q) => $q->whereIn('difficulty', $difficulties)
            )
            ->when($academicLevels, fn ($q) => $q->whereIn('academic_level_id', $academicLevels)
            );

        /* ---------------------------------------------
           CASE 1: SUBJECT + TOPIC + LESSON
        --------------------------------------------- */
        if ($topicId && ($lessonName || $lessonId)) {

            if (! $lessonId && $lessonName) {
                $lessonId = DB::table('lessons')
                    ->where('name', $lessonName)
                    ->value('id');
            }

            if (! $lessonId) {
                return response()->json([]);
            }

            $rows = (clone $base)
                ->where('topic_id', $topicId)
                ->where('lesson_id', $lessonId)
                ->selectRaw('
                    competency_id,
                    question_type,
                    COUNT(*) as total
                ')
                ->groupBy('competency_id', 'question_type')
                ->get();

            if ($rows->isEmpty()) {
                return response()->json([]);
            }

            $competencyData = DB::table('competencies')
                ->whereIn('id', $rows->pluck('competency_id')->unique())
                ->get(['id', 'name']);

            return $this->formatAvailability($rows, $competencyData, 'competency_id');
        }

        /* ---------------------------------------------
           CASE 2: SUBJECT + TOPIC
        --------------------------------------------- */
        if ($topicId) {

            $rows = (clone $base)
                ->where('topic_id', $topicId)
                ->selectRaw('
                    lesson_id,
                    question_type,
                    COUNT(*) as total
                ')
                ->groupBy('lesson_id', 'question_type')
                ->get();

            if ($rows->isEmpty()) {
                return response()->json([]);
            }

            $lessonData = DB::table('lessons')
                ->whereIn('id', $rows->pluck('lesson_id')->unique())
                ->get(['id', 'name']);

            return $this->formatAvailability($rows, $lessonData, 'lesson_id');
        }

        /* ---------------------------------------------
           CASE 3: SUBJECT ONLY → TOPICS
        --------------------------------------------- */
        $rows = (clone $base)
            ->selectRaw('
                topic_id,
                question_type,
                COUNT(*) as total
            ')
            ->groupBy('topic_id', 'question_type')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([]);
        }

        $topicData = DB::table('topics')
            ->whereIn('id', $rows->pluck('topic_id')->unique())
            ->get(['id', 'name']);

        return $this->formatAvailability($rows, $topicData, 'topic_id');
    }

    /* ===============================
       FORMATTER
    =============================== */
    private function formatAvailability($rows, $dataCollection, $key = 'competency_id')
    {
        $typeMap = [
            'multiple_choice' => 'mcq',
            'mcq' => 'mcq',
            'true_false' => 'tf',
            'tf' => 'tf',
            'identification' => 'id',
            'id' => 'id',
            'matching' => 'match',
            'fib' => 'fib',
            'enumeration' => 'enum',
            'enum' => 'enum',
            'essay' => 'essay',
            'mtf' => 'mtf',
        ];

        // Convert collection to map
        $nameMap = $dataCollection->pluck('name', 'id');
        $grouped = $rows->groupBy($key);
        $response = [];

        foreach ($grouped as $id => $items) {

            if (! isset($nameMap[$id])) {
                continue;
            }

            $row = [
                'source_id' => $id,
                'source_type' => $this->detectSourceType($key),
                'source' => $nameMap[$id],
                'mcq' => 0,
                'tf' => 0,
                'mtf' => 0,
                'id' => 0,
                'match' => 0,
                'fib' => 0,
                'enum' => 0,
                'essay' => 0,
            ];

            foreach ($items as $item) {
                $normalized = $typeMap[$item->question_type] ?? null;

                if ($normalized && isset($row[$normalized])) {
                    $row[$normalized] = $item->total;
                }
            }

            $response[] = $row;
        }

        return response()->json($response);
    }

    /* ===============================
       HELPER: Get Source Name
    =============================== */
    private function getSourceName($sourceType, $sourceId)
    {
        switch ($sourceType) {
            case 'topic':
                return Topic::find($sourceId)?->name ?? 'Unknown Topic';
            case 'lesson':
                return Lesson::find($sourceId)?->name ?? 'Unknown Lesson';
            case 'competency':
                return Competency::find($sourceId)?->name ?? 'Unknown Competency';
            default:
                return 'Unknown';
        }
    }

    private function detectSourceType($key)
    {
        return match ($key) {
            'topic_id' => 'topic',
            'lesson_id' => 'lesson',
            'competency_id' => 'competency',
            default => 'unknown',
        };
    }

    /* ===============================
   GENERATE TEST QUESTIONS
=============================== */
    private function generateTestQuestions(Test $test, $questionSettings)
    {
        // Clear old questions
        $test->testQuestions()->delete();

        $order = 1;

        foreach ($questionSettings as $setting) {

            $query = Question::query()
                ->where($setting['source_type'].'_id', $setting['source_id'])
                ->whereIn('difficulty', $test->difficulty)
                ->whereIn('academic_level_id', $test->academic_levels);

            // Pull MCQ
            if ($setting['mcq'] > 0) {
                $questions = (clone $query)
                    ->where('question_type', 'mcq')
                    ->inRandomOrder()
                    ->limit($setting['mcq'])
                    ->get();

                foreach ($questions as $q) {
                    $test->testQuestions()->create([
                        'question_id' => $q->id,
                        'order' => $order++,
                        'points' => 1,
                    ]);
                }
            }

            // Repeat for other types...
            // true_false, mtf, id, match, fib, enum, essay
            // (I can generate the full block if you want)
        }
    }

    /* ===============================
       GENERATE AVAILABILITY
    =============================== */
    private function generateTestAvailability(Test $test)
    {
        $test->testAvailability()->delete();

        $test->testAvailability()->create([
            'is_published' => false,
            'show_score_immediately' => false,
            'show_correct_answers' => false,
            'allow_retakes' => false,
            'allow_practice' => false,
        ]);
    }

    // In TestBuilderController or SaveBuilderController
    public function savePointsToSession(Request $request)
    {
        // Expects: $request->question_type_points = ['mcq' => 1, 'tf' => 1, ...]
        session(['test_points.question_types' => $request->input('question_type_points', [])]);

        return response()->json(['success' => true]);
    }
}
