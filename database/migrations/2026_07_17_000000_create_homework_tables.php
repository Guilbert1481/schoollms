<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Homework, tied to a class (subject × section × teacher) — the same source of
 * truth as grading and attendance, so one teacher posts homework across many
 * subjects and sections. Students submit text + an optional file; the teacher
 * scores each submission.
 *
 * grade_component_id / grading_period are the hooks for the later automatic
 * gradebook feed (a homework can count toward a grade component).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();

            $table->string('title', 200);
            $table->text('instructions')->nullable();
            $table->decimal('points', 6, 2)->nullable();
            $table->dateTime('due_at')->nullable();

            // Gradebook-feed hooks (used in Phase B).
            $table->unsignedTinyInteger('grading_period')->nullable();
            $table->foreignId('grade_component_id')->nullable()->constrained('grade_components')->nullOnDelete();

            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['class_id', 'is_published']);
        });

        Schema::create('homework_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('homework_id')->constrained('homework')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->text('body')->nullable();
            $table->string('file_path')->nullable();   // on the private disk
            $table->string('file_name')->nullable();    // original name, for display
            $table->dateTime('submitted_at')->nullable();

            $table->decimal('score', 6, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['homework_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_submissions');
        Schema::dropIfExists('homework');
    }
};
