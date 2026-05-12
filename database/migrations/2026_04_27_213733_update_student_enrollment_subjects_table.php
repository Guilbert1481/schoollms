<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('student_enrollment_subjects', function (Blueprint $table) {

            // Make class_id nullable (since modular doesn't need it)
            if (Schema::hasColumn('student_enrollment_subjects', 'class_id')) {
                $table->unsignedBigInteger('class_id')->nullable()->change();
            }

            // Modality (VERY IMPORTANT)
            if (! Schema::hasColumn('student_enrollment_subjects', 'modality')) {
                $table->enum('modality', ['modular', 'online', 'face_to_face'])
                      ->default('modular')
                      ->after('subject_id');
            }

            // Grading fields
            if (! Schema::hasColumn('student_enrollment_subjects', 'final_grade')) {
                $table->decimal('final_grade', 5, 2)->nullable()->after('modality');
            }
            if (! Schema::hasColumn('student_enrollment_subjects', 'remarks')) {
                $table->string('remarks')->nullable()->after('final_grade');
            }

            // Status tracking
            if (! Schema::hasColumn('student_enrollment_subjects', 'status')) {
                $table->enum('status', ['ongoing', 'completed', 'dropped'])
                      ->default('ongoing')
                      ->after('remarks');
            }

            // Progress tracking
            if (! Schema::hasColumn('student_enrollment_subjects', 'progress_percentage')) {
                $table->integer('progress_percentage')
                      ->default(0)
                      ->after('status');
            }

            // Timeline
            if (! Schema::hasColumn('student_enrollment_subjects', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('progress_percentage');
            }
            if (! Schema::hasColumn('student_enrollment_subjects', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
        });
    }

    public function down()
    {
        Schema::table('student_enrollment_subjects', function (Blueprint $table) {

            $columns = [
                'modality',
                'final_grade',
                'remarks',
                'status',
                'progress_percentage',
                'started_at',
                'completed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('student_enrollment_subjects', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Revert class_id (optional: remove nullable if needed)
            // $table->unsignedBigInteger('class_id')->nullable(false)->change();
        });
    }
};