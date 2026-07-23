<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A participant in a game_session — the host, a joined student, or a CPU
 * placeholder (solo mode). student_id is the roster link (names/avatars);
 * user_id is the login that joined (null for CPU). Team A/B drives the rope.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_session_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();

            $table->string('display_name', 80);
            $table->string('team', 8)->default('none');   // A | B | none (solo)
            $table->string('role', 8)->default('player'); // host | player
            $table->string('status', 12)->default('ready'); // ready | thinking | answered
            $table->boolean('is_cpu')->default(false);
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->string('avatar_seed', 40)->nullable(); // placeholder-avatar variety
            $table->timestamps();

            $table->unique(['game_session_id', 'user_id']);
            $table->index(['game_session_id', 'team']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_session_players');
    }
};
