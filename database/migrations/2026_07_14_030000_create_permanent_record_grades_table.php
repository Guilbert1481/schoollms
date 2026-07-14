<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The learner's permanent-record grades for Form 137 — the authoritative store
 * the registrar enters. Keyed by (student, grade-level node, subject) so a
 * grade can be recorded for ANY grade level, including ones the learner never
 * formally enrolled in here (e.g. a transferee's marks from another school),
 * with the school it was transferred from, the school year, and the teacher.
 *
 * Form 137 reads this first and falls back to student_enrollment_subjects for
 * grade levels where the learner is enrolled and no permanent-record row exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permanent_record_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('education_node_id')->constrained('education_nodes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->string('status', 32)->nullable();          // passed | failed | credit | ongoing
            $table->string('teacher_name')->nullable();        // free text (may be from another school)
            $table->string('transferred_from')->nullable();    // source school for credited subjects
            $table->string('school_year', 32)->nullable();     // free text SY label
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'education_node_id', 'subject_id'], 'prg_student_node_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permanent_record_grades');
    }
};
