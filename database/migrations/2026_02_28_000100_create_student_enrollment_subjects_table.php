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
        Schema::create('student_enrollment_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_enrollment_id')
                  ->constrained('student_enrollments')
                  ->cascadeOnDelete();

            $table->foreignId('class_id')
                  ->constrained('classes')
                  ->cascadeOnDelete();

            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();

            $table->decimal('grade', 5, 2)->nullable();

            $table->enum('status', [
                'enrolled',
                'dropped',
                'completed'
            ])->default('enrolled');

            $table->timestamps();

            // Prevent duplicate subject enrollment inside same semester
            $table->unique(
                ['student_enrollment_id', 'class_id'],
                'unique_student_class_enrollment'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollment_subjects');
    }
};
