<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Raw teacher-entered component scores — the draft the grading engine computes a
 * final from. One row = one student's score for one grade component in one
 * context:
 *
 *   - higher ed:  keyed by class_id (a class is one subject for a term).
 *   - basic ed:   keyed by education_node_id + subject_id + grading_period
 *                 (a learning area in one quarter).
 *
 * Writes funnel through GradebookService, which upserts on the natural key; the
 * composite unique index backstops the higher-ed case (class_id present).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('grade_component_id')->constrained('grade_components')->cascadeOnDelete();

            // Context — which are set depends on track (see class docblock).
            $table->foreignId('class_id')->nullable()->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('education_node_id')->nullable()->constrained('education_nodes')->nullOnDelete();
            $table->unsignedTinyInteger('grading_period')->nullable();

            $table->decimal('score', 5, 2)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['class_id', 'student_id']);
            $table->index(['education_node_id', 'subject_id', 'grading_period'], 'component_scores_node_area_period_idx');

            $table->unique(
                ['student_id', 'grade_component_id', 'class_id', 'subject_id', 'education_node_id', 'grading_period'],
                'component_scores_natural_key_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_scores');
    }
};
