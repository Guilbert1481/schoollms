<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The fixed question set drawn for a session at start, in play order.
 *
 * Question text / choices / correct answer are SNAPSHOTTED here so grading is
 * server-authoritative (correct_index / answer_text stay server-side until the
 * reveal) and an in-flight game survives edits or deletion of the bank row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_session_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->unsignedSmallInteger('sequence'); // 1-based play order

            $table->text('question_text');
            $table->json('choices')->nullable();                 // mcq: shuffled option texts
            $table->unsignedTinyInteger('correct_index')->nullable(); // mcq correct (into choices)
            $table->string('answer_text', 500)->nullable();      // identification / mcq correct text
            $table->text('explanation')->nullable();

            // Outcome (set when the first CORRECT buzz closes the question).
            $table->foreignId('answered_by_player_id')->nullable()->constrained('game_session_players')->nullOnDelete();
            $table->string('answered_team', 8)->nullable();      // A | B
            $table->boolean('was_correct')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['game_session_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_session_questions');
    }
};
