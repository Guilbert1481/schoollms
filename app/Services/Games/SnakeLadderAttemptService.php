<?php

namespace App\Services\Games;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\Tests\OnlineTestAttemptService;
use Illuminate\Support\Facades\DB;

/**
 * Quiz Snakes & Ladders — the GRADED board-game mode, a thin presentation
 * adapter over the standard online-attempt machinery (the same pattern as Quiz
 * Speed Dash). The attempt row, frozen question set, shuffle seed, and final
 * academic grade are all the shared pipeline; this service adds the board.
 *
 * Two scores, two worlds:
 *  - ACADEMIC — is_correct / points_earned on test_attempt_answers, totalled by
 *    OnlineTestGradingService at submit exactly like a standard quiz. Board
 *    position, dice luck, snakes, ladders, and shields never touch it.
 *  - GAME — token position, dice log, streaks, shields, and game points live
 *    ONLY in test_attempts.meta['game'].
 *
 * Server-authoritative movement: a correct answer arms exactly one dice roll
 * for that question; the roll endpoint takes only the question id — the dice
 * value comes from random_int on the server, the landing tile from
 * SnakesBoard::resolveMove against the attempt's FROZEN layout. Re-requesting
 * a roll replays the stored result (idempotent); rolling unarmed is a 422.
 *
 * Reaching tile 100 never ends a graded sitting — the board is simply marked
 * completed and the remaining questions keep coming (and the reverse: the
 * attempt finishes when the questions do, wherever the token stands).
 */
class SnakeLadderAttemptService
{
    public const PLAY_MODE = 'snake_and_ladder';

    /** Same gate types as Speed Dash: one right answer out of 2–5 choices. */
    public const GAME_TYPES = ['true_false', 'multiple_choice'];

    public const MIN_CHOICES = 2;

    public const MAX_CHOICES = 5;

    private const BASE_POINTS = 100;

    /** Every 3-correct streak earns a Snake Shield (auto-consumed on a snake). */
    private const SHIELD_STREAK = 3;

    private const MAX_SHIELDS = 3;

    public function __construct(private OnlineTestAttemptService $attempts) {}

    // ---------------------------------------------------------------------
    // Settings
    // ---------------------------------------------------------------------

    /** @return array<string, string|bool> */
    public function defaults(): array
    {
        return [
            'movement_policy' => SnakesBoard::POLICY_CLASSIC,
            'finish_rule' => SnakesBoard::FINISH_EXACT,
            'board_layout' => 'default',   // default | random (seeded per attempt)
            'shield_enabled' => true,
        ];
    }

    /** @return array<string, string|bool> merged defaults + the teacher's overrides, whitelisted */
    public function settingsFor(Test $test): array
    {
        $merged = array_merge($this->defaults(), (array) ($test->settings?->game_settings ?? []));

        return [
            'movement_policy' => in_array($merged['movement_policy'], SnakesBoard::POLICIES, true)
                ? $merged['movement_policy'] : SnakesBoard::POLICY_CLASSIC,
            'finish_rule' => in_array($merged['finish_rule'], SnakesBoard::FINISH_RULES, true)
                ? $merged['finish_rule'] : SnakesBoard::FINISH_EXACT,
            'board_layout' => $merged['board_layout'] === 'random' ? 'random' : 'default',
            'shield_enabled' => (bool) $merged['shield_enabled'],
        ];
    }

    public function isEnabled(Test $test): bool
    {
        return ($test->settings?->play_mode ?? 'standard') === self::PLAY_MODE;
    }

    // ---------------------------------------------------------------------
    // Eligibility
    // ---------------------------------------------------------------------

    /**
     * Same shape as Speed Dash: only True/False and Multiple Choice with 2–5
     * options and exactly one correct answer. Checked at teacher-enable AND at
     * student start.
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
                continue;
            }
            $label = 'Q'.$tq->order.' ("'.mb_strimwidth((string) $q->question_text, 0, 40, '…').'")';

            if (! in_array($q->question_type, self::GAME_TYPES, true)) {
                $reasons[] = $label.' is '.str_replace('_', ' ', (string) $q->question_type)
                    .' — only True/False and Multiple Choice can be played on the board.';

                continue;
            }

            $count = $q->choices->count();
            if ($count < self::MIN_CHOICES || $count > self::MAX_CHOICES) {
                $reasons[] = $label.' has '.$count.' choices — the board supports '
                    .self::MIN_CHOICES.'–'.self::MAX_CHOICES.'.';
            }
            if ($q->choices->where('is_correct', true)->count() !== 1) {
                $reasons[] = $label.' must have exactly one correct answer.';
            }
        }

        return ['ok' => $reasons === [], 'reasons' => $reasons];
    }

    // ---------------------------------------------------------------------
    // Game payload + state
    // ---------------------------------------------------------------------

    /**
     * Everything the board screen needs, nothing it must not have: the frozen
     * seeded questions (ids + text only, never is_correct for unanswered
     * items), the frozen board layout, and the resume state — position, roll
     * log, and which question (if any) still has an armed dice roll.
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
                    'choices' => $q['choices'], // [{id, text}] frozen seeded order
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
     * The current game state, initialised (and the board layout FROZEN) on
     * first read of a new attempt. Lives in meta['game'] only.
     *
     * @return array<string, mixed>
     */
    public function gameState(TestAttempt $attempt): array
    {
        $existing = (array) ($attempt->meta['game'] ?? []);
        if (isset($existing['board'])) {
            return $existing + ['pending_roll' => null];
        }

        $settings = $this->settingsFor($attempt->test);
        $board = $settings['board_layout'] === 'random'
            ? SnakesBoard::randomLayout((int) ($attempt->print_seed ?: $attempt->id * 7919))
            : SnakesBoard::defaultLayout();
        // A generated board that somehow fails validation falls back to the
        // curated default — a student sitting must never open on a bad board.
        if (SnakesBoard::validate($board) !== []) {
            $board = SnakesBoard::defaultLayout();
        }

        $fresh = [
            'position' => 1,
            'board' => $board,
            'score' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'correct' => 0,
            'wrong' => 0,
            'shields' => 0,
            'shields_earned' => 0,
            'shields_used' => 0,
            'snakes_hit' => 0,
            'ladders_climbed' => 0,
            'rolls' => [],          // [{q, dice, from, to, event, shielded}]
            'pending_roll' => null, // question_id whose correct answer armed the dice
            'board_completed' => false,
        ];

        $meta = $attempt->meta ?? [];
        $meta['game'] = $fresh;
        $attempt->update(['meta' => $meta]);

        return $fresh;
    }

    // ---------------------------------------------------------------------
    // Answering
    // ---------------------------------------------------------------------

    /**
     * Grade ONE question server-side, exactly once (first write wins, replays
     * return the stored outcome). A correct answer arms the dice for this
     * question; a wrong answer moves nothing and resets the streak.
     *
     * @return array{
     *     ok: bool, duplicate: bool, correct: bool, points: int,
     *     correct_choice_id: ?int, explanation: ?string, game: array<string, mixed>
     * }
     */
    public function answer(TestAttempt $attempt, int $questionId, int $choiceId): array
    {
        $reveal = (bool) ($attempt->test->settings?->show_correct_answers);

        return DB::transaction(function () use ($attempt, $questionId, $choiceId, $reveal) {
            $answer = $attempt->answers()->where('question_id', $questionId)->lockForUpdate()->first();
            abort_unless($answer, 404, 'That question is not part of this attempt.');
            abort_unless(in_array($answer->question_type, self::GAME_TYPES, true), 422, 'That question cannot be answered in game mode.');

            $question = $answer->question()->with('choices')->first();
            $correctChoice = $question?->choices->firstWhere('is_correct', true);

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

            $chosen = $question?->choices->firstWhere('id', $choiceId);
            abort_unless($chosen, 422, 'That choice does not belong to this question.');

            $correct = $correctChoice !== null && (int) $chosen->id === (int) $correctChoice->id;
            $possible = (float) $answer->points_possible;

            // Freeze the ACADEMIC outcome — the exact shape the shared grader
            // recomputes at submit, so the final tally can never disagree.
            $answer->update([
                'response' => ['choice_id' => (int) $chosen->id],
                'is_correct' => $correct,
                'points_earned' => $correct ? $possible : 0.0,
                'needs_manual' => false,
                'answered_at' => now(),
            ]);

            $game = $this->applyAnswerRules($attempt, $questionId, $correct);

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

    // ---------------------------------------------------------------------
    // Rolling + movement
    // ---------------------------------------------------------------------

    /**
     * Roll the dice for a correctly-answered question and move the token —
     * all server-side. The client sends only the question id: the dice value,
     * the landing tile, and any snake/ladder/shield resolution are computed
     * here against the attempt's frozen layout. Idempotent per question — a
     * replayed request returns the stored roll without moving again.
     *
     * @return array{
     *     ok: bool, duplicate: bool, dice: int, from: int, landed: int, to: int,
     *     event: ?array{type: string, from: int, to: int}, shielded: bool,
     *     bounced: bool, moved: bool, board_completed: bool, game: array<string, mixed>
     * }
     */
    public function roll(TestAttempt $attempt, int $questionId): array
    {
        return DB::transaction(function () use ($attempt, $questionId) {
            // Serialize concurrent rolls on the same attempt.
            $locked = TestAttempt::query()->whereKey($attempt->id)->lockForUpdate()->first();
            $g = $this->gameState($locked);

            // Replay: this question already produced its one roll.
            foreach ($g['rolls'] as $entry) {
                if ((int) $entry['q'] === $questionId) {
                    return $this->rollResult($entry, true, $g);
                }
            }

            abort_unless((int) ($g['pending_roll'] ?? 0) === $questionId, 422,
                'No dice roll is available for that question.');

            $settings = $this->settingsFor($locked->test);
            $difficulty = $locked->answers()->where('question_id', $questionId)->first()
                ?->question()->value('difficulty');

            $range = SnakesBoard::diceRange($settings['movement_policy'], $difficulty);
            $dice = $this->rollDice($range['min'], $range['max']);
            // Accuracy Movement: every 3-correct streak adds 1 bonus tile.
            if ($settings['movement_policy'] === SnakesBoard::POLICY_ACCURACY
                && $g['streak'] > 0 && $g['streak'] % self::SHIELD_STREAK === 0) {
                $dice++;
            }

            $move = SnakesBoard::resolveMove((int) $g['position'], $dice, $g['board'], $settings['finish_rule']);

            $shielded = false;
            $to = $move['to'];
            if (($move['event']['type'] ?? null) === 'snake') {
                if ($settings['shield_enabled'] && $g['shields'] > 0) {
                    $g['shields']--;
                    $g['shields_used']++;
                    $shielded = true;
                    $to = $move['landed']; // the shield absorbs the slide
                } else {
                    $g['snakes_hit']++;
                }
            } elseif (($move['event']['type'] ?? null) === 'ladder') {
                $g['ladders_climbed']++;
            }

            $entry = [
                'q' => $questionId,
                'dice' => $dice,
                'from' => $move['from'],
                'landed' => $move['landed'],
                'to' => $to,
                'moved' => $move['moved'],
                'bounced' => $move['bounced'],
                'event' => $move['event'],
                'shielded' => $shielded,
            ];

            $g['position'] = $to;
            $g['rolls'][] = $entry;
            $g['pending_roll'] = null;
            if ($to === SnakesBoard::SIZE) {
                // Reaching FINISH marks the board complete — the graded sitting
                // continues until every required question is answered.
                $g['board_completed'] = true;
                $g['score'] += 250;
            }

            $meta = $locked->meta ?? [];
            $meta['game'] = $g;
            $locked->update(['meta' => $meta]);

            return $this->rollResult($entry, false, $g);
        });
    }

    /** Secure dice — overridable only by tests, never by clients. */
    protected function rollDice(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>  $game
     * @return array<string, mixed>
     */
    private function rollResult(array $entry, bool $duplicate, array $game): array
    {
        return [
            'ok' => true,
            'duplicate' => $duplicate,
            'dice' => (int) $entry['dice'],
            'from' => (int) $entry['from'],
            'landed' => (int) $entry['landed'],
            'to' => (int) $entry['to'],
            'event' => $entry['event'],
            'shielded' => (bool) $entry['shielded'],
            'bounced' => (bool) ($entry['bounced'] ?? false),
            'moved' => (bool) ($entry['moved'] ?? true),
            'board_completed' => (bool) ($game['board_completed'] ?? false),
            'game' => $game,
        ];
    }

    /**
     * Game bookkeeping for one graded answer — arms the dice on a correct
     * answer, earns shields on streaks. meta['game'] only.
     *
     * @return array<string, mixed> the new game state (plus 'last_points')
     */
    private function applyAnswerRules(TestAttempt $attempt, int $questionId, bool $correct): array
    {
        $settings = $this->settingsFor($attempt->test);
        $g = $this->gameState($attempt);
        $points = 0;

        if ($correct) {
            $g['correct']++;
            $g['streak']++;
            $g['best_streak'] = max($g['best_streak'], $g['streak']);
            $g['pending_roll'] = $questionId;

            $points = self::BASE_POINTS + min(($g['streak'] - 1) * 10, 50);
            $g['score'] += $points;

            if ($settings['shield_enabled']
                && $g['streak'] % self::SHIELD_STREAK === 0
                && $g['shields'] < self::MAX_SHIELDS) {
                $g['shields']++;
                $g['shields_earned']++;
            }
        } else {
            $g['wrong']++;
            $g['streak'] = 0;
            // Wrong answer: no movement — the dice stay locked for this question.
        }

        $meta = $attempt->meta ?? [];
        $meta['game'] = $g;
        $attempt->update(['meta' => $meta]);

        return array_merge($g, ['last_points' => $points]);
    }
}
