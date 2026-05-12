<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {

            if (!Schema::hasColumn('student_enrollments', 'school_id')) {
                $table->foreignId('school_id')
                      ->nullable()
                      ->after('id')
                      ->constrained()
                      ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('student_enrollments', 'campus_id')) {
                $table->foreignId('campus_id')
                      ->nullable()
                      ->after('program_id')
                      ->constrained()
                      ->nullOnDelete();
            }

            if (!Schema::hasColumn('student_enrollments', 'education_level')) {
                // kinder | elementary | junior_high | senior_high | undergraduate | graduate
                $table->string('education_level', 32)
                      ->nullable()
                      ->after('program_id');
            }

            if (!Schema::hasColumn('student_enrollments', 'program_type')) {
                // regular | bridging | non_degree
                $table->string('program_type', 32)
                      ->default('regular')
                      ->after('education_level');
            }

            if (!Schema::hasColumn('student_enrollments', 'enrollee_type')) {
                // new | continuing | transferee | returnee | irregular | special | cross_enrollee
                $table->string('enrollee_type', 32)
                      ->default('new')
                      ->after('program_type');
            }

            if (!Schema::hasColumn('student_enrollments', 'enrollment_setting_id')) {
                $table->foreignId('enrollment_setting_id')
                      ->nullable()
                      ->after('term_id')
                      ->constrained('enrollment_settings')
                      ->nullOnDelete();
            }

            if (!Schema::hasColumn('student_enrollments', 'section_id')) {
                $table->foreignId('section_id')
                      ->nullable()
                      ->after('enrollment_setting_id')
                      ->constrained()
                      ->nullOnDelete();
            }

            if (!Schema::hasColumn('student_enrollments', 'total_units')) {
                $table->decimal('total_units', 5, 2)
                      ->nullable()
                      ->after('section_id');
            }

            if (!Schema::hasColumn('student_enrollments', 'approved_by')) {
                $table->foreignId('approved_by')
                      ->nullable()
                      ->after('total_units')
                      ->constrained('users')
                      ->nullOnDelete();
            }

            if (!Schema::hasColumn('student_enrollments', 'approved_at')) {
                $table->timestamp('approved_at')
                      ->nullable()
                      ->after('approved_by');
            }

            if (!Schema::hasColumn('student_enrollments', 'approval_level')) {
                // auto | principal | subject_coordinator | program_head | dean
                $table->string('approval_level', 32)
                      ->nullable()
                      ->after('approved_at');
            }

            if (!Schema::hasColumn('student_enrollments', 'remarks')) {
                $table->text('remarks')
                      ->nullable()
                      ->after('approval_level');
            }
        });

        // Replace status enum with string so the lifecycle can grow:
        // draft | submitted | assessed | billed | partially_paid | enrolled | dropped | cancelled | completed
        DB::statement("ALTER TABLE student_enrollments MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('student_enrollments', 'remarks')) {
                $table->dropColumn('remarks');
            }
            if (Schema::hasColumn('student_enrollments', 'approval_level')) {
                $table->dropColumn('approval_level');
            }
            if (Schema::hasColumn('student_enrollments', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('student_enrollments', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('student_enrollments', 'total_units')) {
                $table->dropColumn('total_units');
            }
            if (Schema::hasColumn('student_enrollments', 'section_id')) {
                $table->dropForeign(['section_id']);
                $table->dropColumn('section_id');
            }
            if (Schema::hasColumn('student_enrollments', 'enrollment_setting_id')) {
                $table->dropForeign(['enrollment_setting_id']);
                $table->dropColumn('enrollment_setting_id');
            }
            if (Schema::hasColumn('student_enrollments', 'enrollee_type')) {
                $table->dropColumn('enrollee_type');
            }
            if (Schema::hasColumn('student_enrollments', 'program_type')) {
                $table->dropColumn('program_type');
            }
            if (Schema::hasColumn('student_enrollments', 'education_level')) {
                $table->dropColumn('education_level');
            }
            if (Schema::hasColumn('student_enrollments', 'campus_id')) {
                $table->dropForeign(['campus_id']);
                $table->dropColumn('campus_id');
            }
            if (Schema::hasColumn('student_enrollments', 'school_id')) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            }
        });
    }
};
