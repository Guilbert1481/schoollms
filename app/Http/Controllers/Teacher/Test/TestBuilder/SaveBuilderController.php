<?php

namespace App\Http\Controllers\Teacher\Test\TestBuilder;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestQuestionTypePoint;
use App\Models\TestSetting;
use App\Models\TestSource; // <-- ADD THIS for the points model
use App\Support\SubjectScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaveBuilderController extends Controller
{
    public function save(Request $request)
    {
        // A teacher may only build tests for subjects assigned to them. Checked
        // up front (before the transaction) so it returns a clean 403 rather
        // than being swallowed by the catch below. Hiding the dropdown is not
        // enough — the subject_id is client-supplied.
        $user = auth()->user();
        if ($user && $user->role === 'teacher' && $request->filled('subject_id')) {
            $assigned = SubjectScope::restrictToTeacher(
                Subject::whereKey($request->input('subject_id')),
                $user
            )->exists();

            if (! $assigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to that subject.',
                ], 403);
            }
        }

        try {
            // Validation
            $validated = $request->validate([
                'class_id' => 'nullable|exists:classes,id',
                'subject_id' => 'required|exists:subjects,id',
                'topic_id' => 'nullable|exists:topics,id',
                'lesson_id' => 'nullable|exists:lessons,id',
                'competency_id' => 'nullable|exists:competencies,id',
                'difficulty' => 'required|array|min:1',
                'academic_levels' => 'required|array|min:1',
                'question_settings' => 'required|array|min:1',
                'timer_minutes' => 'nullable|integer',
                'start_at' => 'nullable',
                'end_at' => 'nullable',
                'shuffle_questions' => 'boolean',
                'shuffle_mcq_choices' => 'boolean',
                'attempts_allowed' => 'nullable|integer',
                'passing_score' => 'nullable|integer|min:0|max:100',
                'show_results' => 'nullable|string',
                'show_correct_answers' => 'nullable|string',
                'sections' => 'nullable|array',
                'mode' => 'nullable|string',
                'term' => 'nullable|string|in:prelim,midterm,finals',
                'assessment_type' => 'nullable|string',
                'grade_component_id' => 'nullable|integer',
            ]);

            DB::beginTransaction();

            // 1. Save Test
            $test = Test::updateOrCreate(
                ['id' => $request->test_id],
                [
                    'school_id' => auth()->user()->school_id,
                    'teacher_id' => auth()->id(),
                    'subject_id' => $request->subject_id,
                    'class_id' => $request->class_id ?: null,
                    'difficulty' => $request->difficulty,
                    'academic_levels' => $request->academic_levels,
                    'title' => 'Untitled Test',
                    'status' => 'draft',
                    // Tag that makes this test's OMR scores feed the gradebook.
                    'grade_component_id' => $this->validComponentId($request->input('grade_component_id')),
                ]
            );

            // 2. Save TestSetting
            //
            // Delivery mode decides whether the online-only settings apply at all. An
            // F2F test is printed and scanned, so the builder hides the Test Settings
            // block entirely and we null those columns here — a stale value left in a
            // hidden input can never survive onto an F2F test.
            $isOnline = $request->input('mode') === 'online';

            $settings = TestSetting::updateOrCreate(
                ['test_id' => $test->id],
                [
                    'mode' => $request->input('mode') !== '' ? $request->input('mode') : null,
                    'timer_minutes' => $isOnline && $request->input('timer_minutes') !== ''
                        ? (int) $request->input('timer_minutes') : null,
                    'attempts_allowed' => $isOnline && $request->input('attempts_allowed') !== ''
                        ? (int) $request->input('attempts_allowed') : null,
                    'passing_score' => $isOnline && $request->input('passing_score') !== ''
                        ? (int) $request->input('passing_score') : null,
                    'show_results' => $isOnline && $request->input('show_results') !== ''
                        ? $request->input('show_results') : null,
                    'show_correct_answers' => $isOnline && $request->input('show_correct_answers') !== ''
                        ? $request->input('show_correct_answers') : null,
                    'shuffle_questions' => $isOnline && $request->boolean('shuffle_questions'),
                    'shuffle_mcq_choices' => $isOnline && $request->boolean('shuffle_mcq_choices'),
                    'term' => $request->input('term') !== '' ? $request->input('term') : null,
                    'assessment_type' => $request->input('assessment_type') !== '' ? $request->input('assessment_type') : null,
                ]
            );

            // Availability (duration vs schedule) is an online-delivery concept. An
            // F2F test carries none of it — that is what lets a printed test save
            // without the teacher inventing a timer or an availability window.
            if (! $isOnline) {
                $settings->availability_mode = null;
                $settings->duration_minutes = null;
                $settings->start_at = null;
                $settings->end_at = null;
                $settings->save();
            } else {
                // The form posts a single timer field and combined datetime-local
                // fields, so match those keys — `start_at`/`end_at`, not the split
                // date/time fields the client never sends (that made schedule mode
                // permanently unreachable).
                $hasDuration = $request->filled('timer_minutes');
                $hasSchedule = $request->filled('start_at') && $request->filled('end_at');

                if ($hasDuration && $hasSchedule) {
                    DB::rollBack();

                    return response()->json([
                        'error' => 'Choose either duration mode or schedule mode, not both.',
                    ], 422);
                }

                if ($hasDuration) {
                    $settings->availability_mode = 'duration';
                    $settings->duration_minutes = (int) $request->timer_minutes;
                    $settings->start_at = null;
                    $settings->end_at = null;
                } elseif ($hasSchedule) {
                    // Combined datetime-local values, stored as-is.
                    $settings->availability_mode = 'schedule';
                    $settings->start_at = $request->start_at;
                    $settings->end_at = $request->end_at;
                    $settings->duration_minutes = null;
                } else {
                    DB::rollBack();

                    return response()->json([
                        'error' => 'Please set either duration or schedule.',
                    ], 422);
                }

                $settings->save();
            }

            // ---- SAVE QUESTION TYPE POINTS TO DB ----
            $questionTypePoints = session('test_points.question_types', []);
            foreach ($questionTypePoints as $type => $points) {
                TestQuestionTypePoint::updateOrCreate(
                    ['test_id' => $test->id, 'question_type' => $type],
                    ['points' => $points]
                );
            }
            // Optionally clear the session after writing
            session()->forget('test_points.question_types');
            // -----------------------------------------

            // 3. Save TestSources
            TestSource::where('test_id', $test->id)->delete();
            foreach ($request->question_settings as $source) {
                TestSource::create([
                    'test_id' => $test->id,
                    'source_type' => $source['source_type'],
                    'source_id' => $source['source_id'],
                    'mcq_count' => $source['mcq'] ?? 0,
                    'tf_count' => $source['tf'] ?? 0,
                    'mtf_count' => $source['mtf'] ?? 0,
                    'identification_count' => $source['id'] ?? 0,
                    'matching_count' => $source['match'] ?? 0,
                    'fib_count' => $source['fib'] ?? 0,
                    'enumeration_count' => $source['enum'] ?? 0,
                    'essay_count' => $source['essay'] ?? 0,
                ]);
            }

            // 4. Generate questions (uses new logic)
            $this->generateTestQuestions($test, $request);

            $this->generateTestAvailability($test);

            DB::commit();

            return response()->json([
                'success' => true,
                'test_id' => $test->id,
                'message' => 'Test saved successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Test save error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error saving test: '.$e->getMessage(),
            ], 500);
        }
    }

    // NEW
    /**
     * Accept a grade-component tag only if it is a real component in this school —
     * the id is client-supplied, and it decides which gradebook column the test's
     * scanned scores land in.
     */
    private function validComponentId($componentId): ?int
    {
        if (! $componentId) {
            return null;
        }

        $exists = DB::table('grade_components as gc')
            ->join('grading_settings as gs', 'gs.id', '=', 'gc.grading_setting_id')
            ->where('gc.id', (int) $componentId)
            ->where('gs.school_id', auth()->user()->school_id)
            ->exists();

        return $exists ? (int) $componentId : null;
    }

    private function generateTestQuestions($test, $request)
    {
        TestQuestion::where('test_id', $test->id)->delete();
        $testSources = TestSource::where('test_id', $test->id)->get();
        $order = 1;

        // Decode JSON fields
        $academicLevels = is_array($test->academic_levels)
            ? $test->academic_levels
            : json_decode($test->academic_levels, true);

        $difficulties = is_array($test->difficulty)
            ? $test->difficulty
            : json_decode($test->difficulty, true);

        // Track used answers globally
        $usedAnswers = [];
        // Track used question IDs (avoid duplicates even with same answer)
        $usedQuestionIds = [];

        foreach ($testSources as $testSource) {
            $questionTypes = [
                'mcq' => $testSource->mcq_count,
                'tf' => $testSource->tf_count,
                'mtf' => $testSource->mtf_count,
                'identification' => $testSource->identification_count,
                'matching' => $testSource->matching_count,
                'fib' => $testSource->fib_count,
                'enumeration' => $testSource->enumeration_count,
                'essay' => $testSource->essay_count,
            ];

            foreach ($questionTypes as $type => $count) {
                if ($count <= 0) {
                    continue;
                }

                $normalizedType = match ($type) {
                    'mcq' => 'multiple_choice',
                    'tf' => 'true_false',
                    'mtf' => 'mtf',
                    'identification' => 'identification',
                    'matching' => 'matching',
                    'fib' => 'fib',
                    'enumeration' => 'enumeration',
                    'essay' => 'essay',
                    default => $type,
                };

                // Get ALL possible candidates (don't limit yet!), SHUFFLED
                $candidates = $this->pullQuestionsUnfiltered(
                    $normalizedType,
                    1000, // fetch plenty
                    $academicLevels,
                    $difficulties,
                    $testSource->source_type === 'topic' ? $testSource->source_id : null,
                    $testSource->source_type === 'lesson' ? $testSource->source_id : null,
                    $testSource->source_type === 'competency' ? $testSource->source_id : null
                );

                $selected = [];
                // 1st Pass: Only pick questions with unique answers (not seen yet)
                foreach ($candidates as $q) {
                    // Get the correct answer for the question
                    $answer = $this->getNormalizedAnswer($q);

                    if (! in_array($answer, $usedAnswers) && ! in_array($q->id, $usedQuestionIds)) {
                        $selected[] = $q;
                        $usedAnswers[] = $answer;
                        $usedQuestionIds[] = $q->id;
                        if (count($selected) >= $count) {
                            break;
                        }
                    }
                }
                // 2nd Pass: If not enough, allow duplicates
                if (count($selected) < $count) {
                    foreach ($candidates as $q) {
                        $answer = $this->getNormalizedAnswer($q);
                        // Avoid the *same* question twice
                        if (! in_array($q->id, $usedQuestionIds)) {
                            $selected[] = $q;
                            $usedQuestionIds[] = $q->id;
                            // (usedAnswers don't matter here)
                            if (count($selected) >= $count) {
                                break;
                            }
                        }
                    }
                }

                // Attach to test
                foreach ($selected as $q) {
                    TestQuestion::create([
                        'test_id' => $test->id,
                        'question_id' => $q->id,
                        'order' => $order++,
                        'points' => 1,
                    ]);
                }

            } // end questionTypes loop
        } // end testSources
    }

    /**
     * The dedup key for $usedAnswers — what makes two questions "the same" when
     * filling a test from several question types.
     *
     * `keyword` is a concept tag the teacher sets, so it is the correct axis: an MCQ
     * answered "The force of gravity acting on an object" and an identification
     * answered "Weight" are the SAME concept with different answer text, and keying
     * on the text alone would put both on one paper. Prefixed so a keyword can never
     * collide with a raw answer string. Falls back to the answer text (then the
     * question text) for questions that carry no keyword yet.
     */
    private function getNormalizedAnswer($question)
    {
        if (! empty($question->keyword)) {
            return 'kw:'.strtolower(trim($question->keyword));
        }

        // Use whatever column/relationship gives the "key" answer: adjust as needed!
        // This covers MCQ, TF, identification, etc.
        // Replace with your actual property/relationship/logic as needed.
        if (isset($question->correct_answer)) {
            return strtolower(trim($question->correct_answer));
        }
        if (isset($question->answer_text)) {
            return strtolower(trim($question->answer_text));
        }
        // For multiple choice with related choices, adjust accordingly
        if (isset($question->choices) && $question->choices->count()) {
            $correct = $question->choices->where('is_correct', true)->first();
            if ($correct) {
                return strtolower(trim($correct->choice_text));
            }
        }

        // fallback to question text if needed
        return strtolower(trim($question->question_text));
    }

    // NEW
    private function pullQuestionsUnfiltered($type, $totalNeeded, $academicLevels, $difficulties, $topicId = null, $lessonId = null, $competencyId = null)
    {
        $query = Question::where('question_type', $type)
            ->whereIn('academic_level_id', $academicLevels)
            ->whereIn('difficulty', $difficulties);

        if ($topicId) {
            $query->where('topic_id', $topicId);
        }
        if ($lessonId) {
            $query->where('lesson_id', $lessonId);
        }
        if ($competencyId) {
            $query->where('competency_id', $competencyId);
        }

        return $query->inRandomOrder()->limit($totalNeeded)->get();
    }

    private function generateTestAvailability(Test $test)
    {
        DB::table('test_availabilities')->where('test_id', $test->id)->delete();
        DB::table('test_availabilities')->insert([
            'test_id' => $test->id,
            'is_published' => false,
            'show_score_immediately' => false,
            'show_correct_answers' => false,
            'allow_retakes' => false,
            'allow_practice' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
