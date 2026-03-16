<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

            $table->foreignId('academic_year_id')
                  ->constrained('academic_years')
                  ->cascadeOnDelete();

            $table->foreignId('semester_id')
                  ->constrained('semesters')
                  ->cascadeOnDelete();

            $table->foreignId('program_id')
                  ->constrained('programs')
                  ->cascadeOnDelete();

            $table->enum('status', [
                'draft',
                'pending',
                'enrolled',
                'dropped',
                'completed',
                'cancelled'
            ])->default('draft');

            $table->timestamps();

            // Prevent duplicate enrollment per semester
            $table->unique(['student_id', 'academic_year_id', 'semester_id'], 'unique_student_semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
