<?php

namespace App\Services\Tests;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Support\TestArrangement;
use Illuminate\Support\Facades\DB;

/**
 * Starts/resumes a student's online attempt and builds the take-screen sections.
 *
 * On start the question set is FROZEN into test_attempt_answers, so editing the test
 * mid-window can't change an in-flight sitting. The per-attempt print_seed drives the
 * same deterministic shuffle the print side uses (TestArrangement), so a student's
 * order is stable across resumes.
 *
 * sections() deliberately NEVER emits is_correct — the answer key stays on the server;
 * grading happens only in OnlineTestGradingService at submit.
 */
class OnlineTestAttemptService
{
    /** Canonical section order + render mode, matching the printed paper. */
    public const SECTIONS = [
        'true_false' => ['title' => 'True or False', 'mode' => 'perq'],
        'mtf' => ['title' => 'Modified True or False', 'mode' => 'perq'],
        'multiple_choice' => ['title' => 'Multiple Choice', 'mode' => 'perq'],
        'matching' => ['title' => 'Matching Type', 'mode' => 'onepage'],
        'fib' => ['title' => 'Fill in the Blank', 'mode' => 'onepage'],
        'identification' => ['title' => 'Identification', 'mode' => 'onepage'],
        'enumeration' => ['title' => 'Enumeration', 'mode' => 'onepage'],
        'essay' => ['title' => 'Essay', 'mode' => 'onepage'],
    ];

    private const BUBBLE = ['multiple_choice', 'true_false', 'mtf'];

    public function __construct(private TestAvailability $availability) {}

    public function activeAttempt(Test $test, int $studentId): ?TestAttempt
    {
        return TestAttempt::where('test_id', $test->id)
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();
    }

    public function attemptsUsed(Test $test, int $studentId): int
    {
        return TestAttempt::where('test_id', $test->id)->where('student_id', $studentId)->count();
    }

    /**
     * Resume the student's in-progress attempt, or start a fresh one — freezing the
     * question set and the server-authoritative deadline.
     */
    public function startOrResume(Test $test, int $studentId, int $schoolId): TestAttempt
    {
        if ($active = $this->activeAttempt($test, $studentId)) {
            return $active;
        }

        return DB::transaction(function () use ($test, $studentId, $schoolId) {
            $settings = $test->settings;
            $seed = ($settings && $settings->shuffle_questions) ? random_int(1, 2_000_000_000) : null;
            $started = now();

            $attempt = TestAttempt::create([
                'school_id' => $schoolId,
                'test_id' => $test->id,
                'student_id' => $studentId,
                'attempt_no' => $this->attemptsUsed($test, $studentId) + 1,
                'status' => 'in_progress',
                'print_seed' => $seed,
                'started_at' => $started,
                'deadline_at' => $this->availability->deadlineFor($test, $started),
            ]);

            foreach ($test->testQuestions()->with('question')->orderBy('order')->get() as $tq) {
                if (! $tq->question || ! isset(self::SECTIONS[$tq->question->question_type])) {
                    continue;
                }
                $attempt->answers()->create([
                    'question_id' => $tq->question->id,
                    'question_type' => $tq->question->question_type,
                    'points_possible' => $tq->points ?: 1,
                    'response' => null,
                ]);
            }

            return $attempt;
        });
    }

    /**
     * Sections for the take screen, in canonical order, shuffled by the attempt's
     * seed. Carries only what the student may see — text, choice ids/labels, and the
     * student's own saved response — never is_correct.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sections(TestAttempt $attempt): array
    {
        $seed = $attempt->print_seed;
        $saved = $attempt->answers()->get()->keyBy('question_id');

        $tqs = $attempt->test->testQuestions()->with('question.choices')->get()
            ->filter(fn ($tq) => $tq->question && isset(self::SECTIONS[$tq->question->question_type]));

        $byType = [];
        foreach ($tqs as $tq) {
            $byType[$tq->question->question_type][] = $tq;
        }

        $out = [];
        foreach (self::SECTIONS as $type => $meta) {
            if (empty($byType[$type])) {
                continue;
            }

            $ordered = collect($byType[$type])
                ->sortBy(fn ($tq) => TestArrangement::orderKey($seed, (int) $tq->order))
                ->values();

            $questions = [];
            $columnB = [];
            foreach ($ordered as $tq) {
                $q = $tq->question;
                $ans = $saved[$q->id] ?? null;

                $choices = [];
                if (in_array($type, self::BUBBLE, true)) {
                    $co = $type === 'multiple_choice'
                        ? TestArrangement::choiceOrder($seed, $q->id, $q->choices)
                        : $q->choices->sortBy('id')->values();
                    foreach ($co as $c) {
                        $choices[] = ['id' => $c->id, 'text' => $c->choice_text];
                    }
                }
                if ($type === 'matching') {
                    $correct = optional($q->choices->firstWhere('is_correct', true))->choice_text;
                    if ($correct !== null) {
                        $columnB[] = $correct;
                    }
                }

                $questions[] = [
                    'question_id' => $q->id,
                    'type' => $type,
                    'text' => $q->question_text,
                    'choices' => $choices,
                    'blanks' => $type === 'enumeration' ? max(1, $q->choices->where('is_correct', true)->count()) : null,
                    'response' => $ans?->response,
                    'review' => (bool) ($ans?->marked_for_review),
                ];
            }

            // Column B is a shuffled pool of the matching answers — no prompt→answer mapping leaks.
            sort($columnB);

            $out[] = [
                'type' => $type,
                'title' => $meta['title'],
                'mode' => $meta['mode'],
                'questions' => $questions,
                'columnB' => $type === 'matching' ? array_values(array_unique($columnB)) : null,
            ];
        }

        return $out;
    }
}
