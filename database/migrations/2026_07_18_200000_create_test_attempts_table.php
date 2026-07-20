<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One student's sitting of an ONLINE test.
     *
     * This is the online-only counterpart to an OMR sheet+result on the F2F side —
     * it never touches the OMR tables. The score is LOCKED AT SUBMIT: each answer's
     * correctness is computed and frozen when the student submits, so a teacher
     * editing the test afterwards can't shift an already-taken grade. `print_seed`
     * freezes the per-attempt shuffle the same way.
     *
     * `deadline_at` is the server-authoritative clock — the browser countdown is
     * cosmetic; the server rejects/auto-submits past this instant regardless of what
     * the client shows. `needs_manual` is true while any essay item is ungraded, so
     * the gradebook post stays provisional until the teacher finishes.
     */
    public function up(): void
    {
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_no')->default(1);

            // in_progress → submitted → graded. auto_submitted flags a timer/window cutoff.
            $table->string('status')->default('in_progress');
            $table->boolean('auto_submitted')->default(false);

            $table->unsignedBigInteger('print_seed')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            // Scores: auto part is known at submit; the essay part waits for the teacher.
            $table->decimal('raw_score', 7, 2)->nullable();
            $table->decimal('max_score', 7, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('needs_manual')->default(false);
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();

            // Anti-cheat / integrity log (tab-blur count, etc.) — advisory, never auto-penalises.
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['test_id', 'student_id', 'attempt_no']);
            $table->index(['student_id', 'status']);
            $table->index(['test_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};
