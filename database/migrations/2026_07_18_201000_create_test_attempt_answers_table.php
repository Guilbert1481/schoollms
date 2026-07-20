<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One student's answer to one question within an attempt.
     *
     * `response` is JSON so every question type fits one column: a chosen choice_id
     * (MCQ/TF/MTF), a prompt→choice map (matching), a string (identification / fill
     * in the blank), an array (enumeration), or free text (essay).
     *
     * `points_possible` is frozen when the attempt starts. `is_correct` and
     * `points_earned` stay NULL until the attempt is submitted (auto types) or the
     * teacher grades it (essay / `needs_manual`) — that is the lock-at-submit rule.
     */
    public function up(): void
    {
        Schema::create('test_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained('test_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('question_type');

            $table->json('response')->nullable();

            $table->decimal('points_possible', 6, 2)->default(1);
            $table->decimal('points_earned', 6, 2)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->boolean('needs_manual')->default(false); // essay: teacher grades it
            $table->boolean('marked_for_review')->default(false);
            $table->timestamp('answered_at')->nullable(); // last autosave

            $table->timestamps();

            $table->unique(['test_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempt_answers');
    }
};
