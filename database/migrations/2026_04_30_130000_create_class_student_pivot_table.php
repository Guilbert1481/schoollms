<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `ClassModel::students()` relation expects a `class_student` pivot
 * table, but only `student_enrollment_subjects` was previously created.
 * The Higher-Ed Irregular wizard's class catalogue calls `withCount('students')`,
 * which fails without this table. We add it as a small pivot so seat counts
 * resolve to 0 while no one has enrolled, and the project can later migrate
 * its enrolment writes here too.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_student')) {
            return;
        }

        Schema::create('class_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_model_id')
                  ->constrained('classes')
                  ->cascadeOnDelete();
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable();
            $table->string('status', 32)->default('enrolled');
            $table->timestamps();

            $table->unique(['class_model_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_student');
    }
};
