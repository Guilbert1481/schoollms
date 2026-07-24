<?php

namespace App\Http\Controllers\Teacher\Test;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestSetting;
use App\Services\Games\SnakeLadderAttemptService;
use App\Services\Games\SnakesBoard;
use App\Services\Games\SpeedDashAttemptService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Teacher → Test Management → "Play as Game": choose how ONE of the teacher's
 * own online tests is delivered — the standard form, Quiz Speed Dash, or Quiz
 * Snakes & Ladders. Delivery-mode only — questions, schedule, attempts, and
 * grading all stay whatever the Test Builder says.
 */
class GameModeController extends Controller
{
    public function __construct(
        private SpeedDashAttemptService $speedDash,
        private SnakeLadderAttemptService $snakeLadder,
    ) {}

    public function edit(Test $test)
    {
        $this->guard($test);

        return view('teacher.tests.game-settings', [
            'test' => $test,
            'playMode' => $test->settings?->play_mode ?? 'standard',
            'dashSettings' => $this->speedDash->settingsFor($test),
            'boardSettings' => $this->snakeLadder->settingsFor($test),
            // Both games gate on the same question shapes, so one eligibility
            // check speaks for both (kept per-service so they can diverge).
            'eligibility' => $this->speedDash->eligibility($test),
            'isOnline' => ($test->settings->mode ?? null) === 'online',
        ]);
    }

    public function update(Request $request, Test $test)
    {
        $this->guard($test);

        $data = $request->validate([
            'play_mode' => ['required', Rule::in(['standard', SpeedDashAttemptService::PLAY_MODE, SnakeLadderAttemptService::PLAY_MODE])],
            // Speed Dash knobs
            'starting_lives' => ['required', 'integer', 'min:1', 'max:5'],
            'instant_submit' => ['required', 'boolean'],
            'powerups_enabled' => ['required', 'boolean'],
            'speed_bonus_max' => ['required', 'integer', 'min:0', 'max:50'],
            // Snakes & Ladders knobs
            'movement_policy' => ['required', Rule::in(SnakesBoard::POLICIES)],
            'finish_rule' => ['required', Rule::in(SnakesBoard::FINISH_RULES)],
            'board_layout' => ['required', Rule::in(['default', 'random'])],
            'shield_enabled' => ['required', 'boolean'],
        ]);

        $mode = $data['play_mode'];

        if ($mode !== 'standard') {
            abort_unless(($test->settings->mode ?? null) === 'online', 422, 'Only online tests can be played as a game.');

            $eligibility = $mode === SnakeLadderAttemptService::PLAY_MODE
                ? $this->snakeLadder->eligibility($test)
                : $this->speedDash->eligibility($test);
            if (! $eligibility['ok']) {
                return back()->with('error', 'Cannot enable the game: '.$eligibility['reasons'][0]);
            }
        }

        $gameSettings = match ($mode) {
            SnakeLadderAttemptService::PLAY_MODE => [
                'movement_policy' => $data['movement_policy'],
                'finish_rule' => $data['finish_rule'],
                'board_layout' => $data['board_layout'],
                'shield_enabled' => (bool) $data['shield_enabled'],
            ],
            SpeedDashAttemptService::PLAY_MODE => [
                'starting_lives' => (int) $data['starting_lives'],
                'instant_submit' => (bool) $data['instant_submit'],
                'powerups_enabled' => (bool) $data['powerups_enabled'],
                'speed_bonus_max' => (int) $data['speed_bonus_max'],
            ],
            default => null,
        };

        $settings = $test->settings ?: new TestSetting(['test_id' => $test->id]);
        $settings->fill(['play_mode' => $mode, 'game_settings' => $gameSettings])->save();

        $names = [
            SpeedDashAttemptService::PLAY_MODE => 'Quiz Speed Dash',
            SnakeLadderAttemptService::PLAY_MODE => 'Quiz Snakes & Ladders',
        ];

        return redirect()->route('teacher.tests.game', $test)
            ->with('success', $mode === 'standard'
                ? 'Game delivery is off — students get the standard quiz screen.'
                : $names[$mode].' is ON — students will take this test as the game.');
    }

    /** BelongsToSchool 404s cross-school at binding; this is author-only within the school. */
    private function guard(Test $test): void
    {
        abort_unless((int) $test->teacher_id === (int) auth()->id(), 403);
    }
}
