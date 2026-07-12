<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-teacher Gamified Quiz "Quiz Mode" settings.
 *
 * When is_on = true, the teacher's content selection (term / year level /
 * subject / topic / lesson / competency) is locked onto the game pages of the
 * students in that teacher's classes, so teacher and students practice the
 * same scoped content. When off, students free-practice whatever they choose.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_quiz_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('is_on')->default(false);
            $table->unsignedTinyInteger('term')->nullable();          // 1..4 grading term
            $table->foreignId('academic_level_id')->nullable()->constrained('academic_levels')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->foreignId('competency_id')->nullable()->constrained('competencies')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'is_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_quiz_settings');
    }
};
