<?php

namespace App\Services\Games;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\Tests\OnlineTestAttemptService;
use Illuminate\Support\Facades\DB;

/**
 * Quiz Speed Dash — the GRADED game mode, as a thin presentation adapter over
 * the existing online-attempt machinery. The attempt row, the frozen question
 * set, the shuffle seed, and the final academic grade are all the standard
 * pipeline (OnlineTestAttemptService + OnlineTestGradingService); this service
 * only adds the game-shaped payload and the per-question answer lock.
 *
 * Two scores, two worlds:
 *  - ACADEMIC — is_correct / points_earned on test_attempt_answers, totalled by
 *    OnlineTestGradingService at submit exactly like a standard quiz.
 *  - GAME — hearts, streaks, boosts, and points that live ONLY in
 *    test_attempts.meta['game']. Nothing here writes raw_score/percentage, so
 *    game mechanics physically cannot move the official grade.
 *
 * Per-question lock: unlike the standard take screen (lock-at-submit), the game
 * needs instant right/wrong feedback, so each answer is graded and FROZEN the
 * moment it is submitted — first write wins, re-submits return the stored
 * outcome (idempotent). The answer key still never leaves the server before
 * that moment, and the final submit re-tallies through the shared grader.
 */
class SpeedDashAttemptService
{
    public const PLAY_MODE = 'speed_dash';

    /** The only bank types the runner can present as gates. */
    public const GAME_TYPES = ['true_false', 'multiple_choice'];

    public const MIN_CHOICES = 2;

    public const MAX_CHOICES = 5;

    /** Base game points per correct answer ("+100 CORRECT!"). */
    private const BASE_POINTS = 100;

    /** Streak-meter thresholds → power-ups (visual + shield). */
    private const BOOST_STREAK = 3;

    private const SHIELD_STREAK = 5;

    public function __construct(private OnlineTestAttemptService $attempts) {}

    // ---------------------------------------------------------------------
    // Settings
    // ---------------------------------------------------------------------

    /**
     * Game defaults — what a test plays like when the teacher just flips the
     * mode on. Every knob is overridable per test via test_settings.game_settings.
     *
     * @return array<string, int|bool>
     */
    public function defaults(): array
    {
        return [
            'starting_lives' => 3,     // hearts; 0 hearts → Recovery Mode, never a dead stop
            'instant_submit' => true,  // false = student confirms with Enter / Confirm button
            'powerups_enabled' => true, // speed boost + shield from streaks
            'speed_bonus_max' => 20,   // small; BASE_POINTS dominates by construction
        ];
    }

    /** @return array<string, int|bool> merged defaults + the teacher's overrides */
    public function settingsFor(Test $test): array
    {
        $merged = array_merge($this->defaults(), (array) ($test->settings?->game_settings ?? []));
        $merged['starting_lives'] = max(1, min(5, (int) $merged['starting_lives']));
        $merged['instant_submit'] = (bool) $merged['instant_submit'];
        $merged['powerups_enabled'] = (bool) $merged['powerups_enabled'];
        $merged['speed_bonus_max'] = max(0, min(50, (int) $merged['speed_bonus_max']));

        return $merged;
    }

    public function isEnabled(Test $test): bool
    {
        return ($test->settings?->play_mode ?? 'standard') === self::PLAY_MODE;
    }

    // ---------------------------------------------------------------------
    // Eligibility
    // ---------------------------------------------------------------------

    /**
     * Can this test be delivered as Speed Dash? Only True/False and Multiple
     * Choice items with 2–5 options and exactly one correct answer fit the
     * answer-gate mechanic. Checked when the teacher enables the mode AND when
     * a student starts, so a test edited after enabling can't half-render.
     *
     * @return array{ok: bool, reasons: array<int, string>}
     */
    public function eligibility(Test $test): array
    {
        $reasons = [];
        $tqs = $test->testQuestions()->with('question.choices')->get();

        if ($tqs->isEmpty()) {
            $reasons[] = 'The test has no questions yet.';
        }

        foreach ($tqs as $tq) {
            $q = $tq->question;
            if (! $q) {
                continue; // dangling link — the standard flow skips these too
            }
            $label = 'Q'.$tq->order.' ("'.mb_strimwidth((string) $q->question_text, 0, 40, '…').'")';

            if (! in_array($q->question_type, self::GAME_TYPES, true)) {
                $reasons[] = $label.' is '.str_replace('_', ' ', (string) $q->question_type)
                    .' — only True/False and Multiple Choice can be played as Speed Dash.';

                continue;
            }

            $count = $q->choices->count();
            if ($count < self::MIN_CHOICES || $count > self::MAX_CHOICES) {
                $reasons[] = $label.' has '.$count.' choices — gates support '
                    .self::MIN_CHOICES.'–'.self::MAX_CHOICES.'.';
            }
            if ($q->choices->where('is_correct', true)->count() !== 1) {
                $reasons[] = $label.' must have exactly one correct answer.';
            }
        }

        return ['ok' => $reasons === [], 'reasons' => $reasons];
    }

    // ---------------------------------------------------------------------
    // Game payload
    // ---------------------------------------------------------------------

    /**
     * Everything the game screen needs, and nothing it must not have: the
     * frozen, seeded question order with A–E gate choices (ids + text only —
     * never is_correct for unanswered items), plus the resume state (which
     * questions are already locked and how they went — those outcomes were
     * already shown to the student live, so replaying them on refresh leaks
     * nothing new).
     *
     * @return array<string, mixed>
     */
    public function payload(TestAttempt $attempt): array
    {
        $answered = $attempt->answers()->get()->keyBy('question_id');

        $questions = [];
        foreach ($this->attempts->sections($attempt) as $section) {
            foreach ($section['questions'] as $q) {
                $lock = $answered[$q['question_id']] ?? null;
                $locked = $lock && $lock->is_correct !== null;

                $questions[] = [
                    'question_id' => $q['question_id'],
                    'type' => $q['type'],
                    'text' => $q['text'],
                    'choices' => $q['choices'], // [{id, text}] in the frozen seeded order
                    'answered' => $locked,
                    'was_correct' => $locked ? (bool) $lock->is_correct : null,
                    'chosen_id' => $locked ? (int) (($lock->response['choice_id'] ?? 0)) : null,
                ];
            }
        }

        return [
            'questions' => $questions,
            'game' => $this->gameState($attempt),
            'settings' => $this->settingsFor($attempt->test),
        ];
    }

    /**
     * The current game meters (hearts, streak, score, …), seeded fresh on a new
     * attempt. Lives in meta['game'] only.
     *
     * @return array<string, mixed>
     */
    public function gameState(TestAttempt $attempt): array
    {
        $settings = $this->settingsFor($attempt->test);

        return array_merge([
            'score' => 0,
            'hearts' => $settings['starting_lives'],
            'shield' => false,
            'streak' => 0,
            'best_streak' => 0,
            'correct' => 0,
            'wrong' => 0,
            'recovery' => false,
            'boosts' => 0,
            'shields_earned' => 0,
        ], (array) ($attempt->meta['game'] ?? []));
    }

    // ---------------------------------------------------------------------
    // Answering
    // ---------------------------------------------------------------------

    /**
     * Grade ONE gate entry, server-side, exactly once.
     *
     * First write wins: the frozen answer row is locked FOR UPDATE and only an
     * ungraded row accepts a write, so double-clicks, retries after a flaky
     * connection, and replayed requests all resolve to the same stored outcome.
     * The correct choice is revealed back only when the test's own
     * show_correct_answers setting says so.
     *
     * @return array{
     *     ok: bool, duplicate: bool, correct: bool, points: int,
     *     correct_choice_id: ?int, explanation: ?string, game: array<string, mixed>
     * }
     */
    public function answer(TestAttempt $attempt, int $questionId, int $choiceId, ?int $responseMs = null): array
    {
        $test = $attempt->test;
        $reveal = (bool) ($test->settings?->show_correct_answers);

        return DB::transaction(function () use ($attempt, $questionId, $choiceId, $responseMs, $reveal) {
            $answer = $attempt->answers()->where('question_id', $questionId)->lockForUpdate()->first();
            abort_unless($answer, 404, 'That question is not part of this attempt.');
            abort_unless(in_array($answer->question_type, self::GAME_TYPES, true), 422, 'That question cannot be answered in game mode.');

            $question = $answer->question()->with('choices')->first();
            $correctChoice = $question?->choices->firstWhere('is_correct', true);

            // Already locked → idempotent replay of the stored outcome, no meters moved.
            if ($answer->is_correct !== null) {
                return [
                    'ok' => true,
                    'duplicate' => true,
                    'correct' => (bool) $answer->is_correct,
                    'points' => 0,
                    'correct_choice_id' => $reveal ? $correctChoice?->id : null,
                    'explanation' => $reveal ? $question?->explanation : null,
                    'game' => $this->gameState($attempt),
                ];
            }

            // The chosen gate must be one of this question's own choices.
            $chosen = $question?->choices->firstWhere('id', $choiceId);
            abort_unless($chosen, 422, 'That choice does not belong to this question.');

            $correct = $correctChoice !== null && (int) $chosen->id === (int) $correctChoice->id;
            $possible = (float) $answer->points_possible;

            // Freeze the ACADEMIC outcome — same shape OnlineTestGradingService
            // recomputes at submit, so the final tally can never disagree.
            $answer->update([
                'response' => ['choice_id' => (int) $chosen->id],
                'is_correct' => $correct,
                'points_earned' => $correct ? $possible : 0.0,
                'needs_manual' => false,
                'answered_at' => now(),
            ]);

            $game = $this->applyGameRules($attempt, $correct, $question?->difficulty, $responseMs);

            return [
                'ok' => true,
                'duplicate' => false,
                'correct' => $correct,
                'points' => (int) ($game['last_points'] ?? 0),
                'correct_choice_id' => $reveal ? $correctChoice?->id : null,
                'explanation' => $reveal ? $question?->explanation : null,
                'game' => $game,
            ];
        });
    }

    /**
     * Move the GAME meters for one graded answer and persist them to
     * meta['game']. Pure game bookkeeping — no academic field is touched.
     *
     * @return array<string, mixed> the new game state (plus 'last_points')
     */
    private function applyGameRules(TestAttempt $attempt, bool $correct, ?string $difficulty, ?int $responseMs): array
    {
        $settings = $this->settingsFor($attempt->test);
        $g = $this->gameState($attempt);
        $points = 0;

        if ($correct) {
            $g['correct']++;
            $g['streak']++;
            $g['best_streak'] = max($g['best_streak'], $g['streak']);

            // Recovery Mode strips every bonus: base points only, no power-ups.
            $points = self::BASE_POINTS;
            if (! $g['recovery']) {
                if ($difficulty === 'advanced') {
                    $points += 25;
                }
                $points += min(($g['streak'] - 1) * 10, 50);
                if ($responseMs !== null && $responseMs >= 0 && $settings['speed_bonus_max'] > 0) {
                    // Full bonus under 3s, fading to 0 at 15s — always small.
                    $points += (int) round($settings['speed_bonus_max'] * max(0, min(1, (15000 - $responseMs) / 12000)));
                }
                if ($settings['powerups_enabled']) {
                    if ($g['streak'] === self::BOOST_STREAK) {
                        $g['boosts']++;
                    }
                    if ($g['streak'] === self::SHIELD_STREAK && ! $g['shield']) {
                        $g['shield'] = true;
                        $g['shields_earned']++;
                    }
                }
            }
            $g['score'] += $points;
        } else {
            $g['wrong']++;
            $g['streak'] = 0;

            if ($g['shield']) {
                $g['shield'] = false; // shield absorbs the penalty
            } elseif ($g['hearts'] > 0) {
                $g['hearts']--;
            }
            if ($g['hearts'] <= 0) {
                // Out of hearts: the game slows down, bonuses stop — but a graded
                // attempt ALWAYS continues to completion.
                $g['recovery'] = true;
            }
        }

        $meta = $attempt->meta ?? [];
        $meta['game'] = $g;
        $attempt->update(['meta' => $meta]);

        return array_merge($g, ['last_points' => $points]);
    }
}
