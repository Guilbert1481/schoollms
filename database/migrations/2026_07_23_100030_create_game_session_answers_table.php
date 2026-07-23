<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One buzz per player per question — the audit log AND the arbitration guard.
 *
 * The unique (question, player) index enforces one attempt per player per
 * question (no spamming until correct); the first row with is_correct = true
 * closes the question and wins the rope pull. "Anyone who knows the answer can
 * answer" — the fastest correct buzz takes the point.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_session_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('game_session_question_id')->constrained('game_session_questions')->cascadeOnDelete();
            $table->foreignId('game_session_player_id')->constrained('game_session_players')->cascadeOnDelete();

            $table->unsignedTinyInteger('choice_index')->nullable(); // mcq pick
            $table->string('answer_text', 500)->nullable();          // identification pick
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['game_session_question_id', 'game_session_player_id'], 'gsa_question_player_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_session_answers');
    }
};
