<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-grading-period grades for the basic-ed Report Card (current school year),
 * e.g. 1st / 2nd / 3rd grading. Keyed by (student, grade-level node, subject,
 * academic year, grading period). The Form 137 keeps the FINAL grade per year;
 * the Report Card breaks the current year down by grading period.
 *
 * Non-basic (higher ed) report cards read student_enrollment_subjects directly
 * (one grade per subject per semester) and do not use this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_card_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('education_node_id')->constrained('education_nodes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->unsignedTinyInteger('grading_period');   // 1..4
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['student_id', 'education_node_id', 'subject_id', 'academic_year_id', 'grading_period'],
                'rcg_unique_period'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_grades');
    }
};
