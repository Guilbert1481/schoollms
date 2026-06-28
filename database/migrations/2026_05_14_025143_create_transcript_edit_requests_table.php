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
        Schema::create('transcript_edit_requests', function (Blueprint $table) {
            $table->id();

            // Subject of the edit
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('enrollment_subject_id')->nullable()
                ->constrained('student_enrollment_subjects')->nullOnDelete();

            // Edit payload (final grade only for now)
            $table->decimal('old_grade', 5, 2)->nullable();
            $table->decimal('new_grade', 5, 2);
            $table->text('reason')->nullable();

            // Workflow
            $table->string('status', 32)->default('pending'); // pending | program_head_approved | dean_approved | applied | rejected
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

            $table->foreignId('program_head_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('program_head_approved_at')->nullable();
            $table->text('program_head_note')->nullable();

            $table->foreignId('dean_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dean_approved_at')->nullable();
            $table->text('dean_note')->nullable();

            $table->timestamp('applied_at')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['student_id', 'subject_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transcript_edit_requests');
    }
};
