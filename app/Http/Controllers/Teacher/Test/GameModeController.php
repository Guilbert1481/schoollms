<?php

namespace App\Http\Controllers\Teacher\Test;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestSetting;
use App\Services\Games\SpeedDashAttemptService;
use Illuminate\Http\Request;

/**
 * Teacher → Test Management → "Play as Game": enable/tune Quiz Speed Dash for
 * ONE of the teacher's own online tests. Delivery-mode only — questions,
 * schedule, attempts, and grading all stay whatever the Test Builder says.
 */
class GameModeController extends Controller
{
    public function __construct(private SpeedDashAttemptService $game) {}

    public function edit(Test $test)
    {
        $this->guard($test);

        return view('teacher.tests.game-settings', [
            'test' => $test,
            'settings' => $this->game->settingsFor($test),
            'enabled' => $this->game->isEnabled($test),
            'eligibility' => $this->game->eligibility($test),
            'isOnline' => ($test->settings->mode ?? null) === 'online',
        ]);
    }

    public function update(Request $request, Test $test)
    {
        $this->guard($test);

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'starting_lives' => ['required', 'integer', 'min:1', 'max:5'],
            'instant_submit' => ['required', 'boolean'],
            'powerups_enabled' => ['required', 'boolean'],
            'speed_bonus_max' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        $enabled = (bool) $data['enabled'];

        if ($enabled) {
            abort_unless(($test->settings->mode ?? null) === 'online', 422, 'Only online tests can be played as a game.');

            $eligibility = $this->game->eligibility($test);
            if (! $eligibility['ok']) {
                return back()->with('error', 'Cannot enable Quiz Speed Dash: '.$eligibility['reasons'][0]);
            }
        }

        $settings = $test->settings ?: new TestSetting(['test_id' => $test->id]);
        $settings->fill([
            'play_mode' => $enabled ? SpeedDashAttemptService::PLAY_MODE : 'standard',
            'game_settings' => [
                'starting_lives' => (int) $data['starting_lives'],
                'instant_submit' => (bool) $data['instant_submit'],
                'powerups_enabled' => (bool) $data['powerups_enabled'],
                'speed_bonus_max' => (int) $data['speed_bonus_max'],
            ],
        ])->save();

        return redirect()->route('teacher.tests.game', $test)
            ->with('success', $enabled
                ? 'Quiz Speed Dash is ON — students will take this test as the runner game.'
                : 'Quiz Speed Dash is off — students get the standard quiz screen.');
    }

    /** BelongsToSchool 404s cross-school at binding; this is author-only within the school. */
    private function guard(Test $test): void
    {
        abort_unless((int) $test->teacher_id === (int) auth()->id(), 403);
    }
}
