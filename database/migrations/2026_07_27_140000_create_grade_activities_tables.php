<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual gradebook records — the teacher's hand-entered peer to online tests and
 * homework. One `grade_activity` = one graded item of work (a quiz, a recitation,
 * a seatwork) for one grade component, in one context:
 *
 *   - higher ed:  keyed by class_id (a class is one subject for a term).
 *   - basic ed:   keyed by education_node_id + subject_id + grading_period
 *                 (a learning area in one quarter).
 *
 * `total_items` is the item count / highest-possible score; each student's raw
 * score for the activity lives in `grade_activity_scores`. These records feed the
 * gradebook exactly like tests and homework do — ComponentScoreRecomputer folds
 * every source into component_scores as Σ raw ÷ Σ total × 100, so a teacher can
 * mix hand-entered and online work in one component with no double counting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('grade_component_id')->constrained('grade_components')->cascadeOnDelete();

            $table->string('title')->nullable();          // optional label, e.g. "Quiz 1"
            $table->unsignedInteger('total_items');        // highest-possible score for the item
            $table->date('activity_date');                 // when it was given/recorded

            // Context — which are set depends on track (mirrors component_scores).
            $table->foreignId('class_id')->nullable()->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('education_node_id')->nullable()->constrained('education_nodes')->nullOnDelete();
            $table->unsignedTinyInteger('grading_period')->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['class_id', 'grade_component_id'], 'grade_activities_class_component_idx');
            $table->index(['education_node_id', 'subject_id', 'grading_period'], 'grade_activities_node_area_period_idx');
        });

        Schema::create('grade_activity_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('grade_activity_id')->constrained('grade_activities')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->decimal('raw_score', 6, 2)->nullable(); // null = not taken / left blank

            $table->timestamps();

            $table->unique(['grade_activity_id', 'student_id'], 'grade_activity_scores_activity_student_unique');
            $table->index(['student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_activity_scores');
        Schema::dropIfExists('grade_activities');
    }
};
