<?php

namespace App\Services\Tests;

use App\Models\Question;
use App\Models\TestAttempt;
use App\Models\TestAttemptAnswer;
use App\Services\Grading\TestComponentFeed;
use App\Support\AnswerMatch;
use Illuminate\Support\Facades\DB;

/**
 * Grades an online attempt and feeds the gradebook. Uses the SAME matching rules as
 * the F2F/OMR side (AnswerMatch for text, choice-id for bubbles) so a question grades
 * identically whichever way it is delivered.
 *
 * Lock-at-submit: correctness and points are computed when the student submits and
 * frozen on each answer, so editing the test afterwards can't move an already-taken
 * grade. Essays can't auto-grade — they park as needs_manual, the attempt posts a
 * PROVISIONAL score (auto items only), and gradeManual() re-tallies + re-feeds once
 * the teacher scores them.
 */
class OnlineTestGradingService
{
    /** Question types graded by a human, never auto. */
    private const MANUAL_TYPES = ['essay'];

    /** Bubble types: the response carries the chosen choice id. */
    private const BUBBLE_TYPES = ['multiple_choice', 'mcq', 'true_false', 'tf', 'mtf'];

    public function __construct(private TestComponentFeed $feed) {}

    /**
     * Submit an in-progress attempt: grade every auto item, freeze the score, feed
     * the gradebook. A no-op on an already-submitted attempt (never silently regrade).
     */
    public function submit(TestAttempt $attempt, bool $auto = false): TestAttempt
    {
        if (! $attempt->isOpen()) {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt, $auto) {
            $answers = $attempt->answers()->with('question.choices')->get();
            $rawAuto = 0.0;
            $max = 0.0;
            $needsManual = false;

            foreach ($answers as $ans) {
                $possible = (float) $ans->points_possible;
                $max += $possible;

                if (in_array($ans->question_type, self::MANUAL_TYPES, true)) {
                    $ans->update(['needs_manual' => true, 'is_correct' => null, 'points_earned' => null]);
                    $needsManual = true;

                    continue;
                }

                [$correct, $earned] = $this->gradeAuto($ans, $ans->question, $possible);
                $ans->update(['is_correct' => $correct, 'points_earned' => $earned, 'needs_manual' => false]);
                $rawAuto += $earned;
            }

            $attempt->update([
                'status' => $needsManual ? 'submitted' : 'graded',
                'auto_submitted' => $auto,
                'submitted_at' => now(),
                'raw_score' => round($rawAuto, 2),
                'max_score' => round($max, 2),
                'percentage' => $max > 0 ? round($rawAuto / $max * 100, 2) : 0,
                'needs_manual' => $needsManual,
                'graded_at' => $needsManual ? null : now(),
            ]);

            $this->feed->sync($attempt->test);

            return $attempt->fresh();
        });
    }

    /**
     * Teacher scores one manual (essay) answer, then the attempt re-tallies and
     * re-feeds the gradebook. Used by the manual-grading UI (a later phase).
     */
    public function gradeManual(TestAttemptAnswer $answer, float $points, ?int $by = null): void
    {
        $possible = (float) $answer->points_possible;
        $earned = max(0.0, min($points, $possible));
        $answer->update([
            'points_earned' => $earned,
            'is_correct' => $earned >= $possible,
            'needs_manual' => false,
        ]);

        $this->recompute($answer->attempt, $by);
    }

    /** Re-total an attempt from its answers (after a manual grade) and re-feed. */
    public function recompute(TestAttempt $attempt, ?int $by = null): void
    {
        $answers = $attempt->answers()->get();
        $raw = (float) $answers->sum(fn ($a) => (float) ($a->points_earned ?? 0));
        $max = (float) $answers->sum(fn ($a) => (float) $a->points_possible);
        $needsManual = $answers->contains(fn ($a) => (bool) $a->needs_manual);

        $attempt->update([
            'raw_score' => round($raw, 2),
            'max_score' => round($max, 2),
            'percentage' => $max > 0 ? round($raw / $max * 100, 2) : 0,
            'needs_manual' => $needsManual,
            'status' => $needsManual ? 'submitted' : 'graded',
            'graded_at' => $needsManual ? null : now(),
            'graded_by' => $by ?? $attempt->graded_by,
        ]);

        $this->feed->sync($attempt->test);
    }

    /**
     * @return array{0: bool, 1: float} [isCorrect, pointsEarned]
     */
    private function gradeAuto(TestAttemptAnswer $ans, ?Question $q, float $possible): array
    {
        if (! $q) {
            return [false, 0.0];
        }

        $r = (array) ($ans->response ?? []);
        $type = $ans->question_type;

        // Bubble types — compare the chosen choice id to the question's correct choice.
        if (in_array($type, self::BUBBLE_TYPES, true)) {
            $chosen = (int) ($r['choice_id'] ?? 0);
            $correctChoice = $q->choices->firstWhere('is_correct', true);
            $ok = $correctChoice !== null && (int) $correctChoice->id === $chosen && $chosen !== 0;

            return [$ok, $ok ? $possible : 0.0];
        }

        // Enumeration — partial credit: each distinct correct item the student hits.
        if (in_array($type, ['enumeration', 'enum'], true)) {
            $accepted = $q->choices->where('is_correct', true)->pluck('choice_text')->all();
            $required = max(1, count($accepted));
            $items = array_filter(
                array_map(fn ($v) => (string) $v, (array) ($r['items'] ?? [])),
                fn ($v) => trim($v) !== ''
            );

            $pool = $accepted;
            $hits = 0;
            foreach ($items as $item) {
                foreach ($pool as $i => $acc) {
                    if (AnswerMatch::matches($item, [$acc])) {
                        unset($pool[$i]);
                        $hits++;
                        break;
                    }
                }
            }
            $hits = min($hits, $required);

            return [$hits === $required, round($possible * $hits / $required, 2)];
        }

        // Text types (identification, fib, matching) — fuzzy match against the key.
        $accepted = $q->choices->where('is_correct', true)->pluck('choice_text')->all();
        $text = (string) ($r['text'] ?? $r['value'] ?? '');
        $ok = $text !== '' && AnswerMatch::matches($text, $accepted);

        return [$ok, $ok ? $possible : 0.0];
    }
}
