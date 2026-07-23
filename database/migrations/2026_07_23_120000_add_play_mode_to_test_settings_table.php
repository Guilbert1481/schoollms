<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional game presentation for an online test.
     *
     * `play_mode` selects how the take screen is DELIVERED — 'standard' (the
     * classic form) or 'speed_dash' (the endless-runner game). It never changes
     * WHAT is graded: both modes write the same test_attempts /
     * test_attempt_answers rows and grade through OnlineTestGradingService.
     *
     * `game_settings` holds the teacher's per-test game tuning (lives, power-ups,
     * instant submit, leaderboard, …) as JSON so adding a knob never needs a
     * schema change. NULL means "use the game defaults".
     */
    public function up(): void
    {
        Schema::table('test_settings', function (Blueprint $table) {
            $table->string('play_mode', 32)->default('standard')->after('mode');
            $table->json('game_settings')->nullable()->after('play_mode');
        });
    }

    public function down(): void
    {
        Schema::table('test_settings', function (Blueprint $table) {
            $table->dropColumn(['play_mode', 'game_settings']);
        });
    }
};
