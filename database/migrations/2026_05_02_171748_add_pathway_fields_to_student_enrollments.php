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
        Schema::table('student_enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('student_enrollments', 'modality_id')) {
                $table->foreignId('modality_id')
                    ->nullable()
                    ->after('program_id')
                    ->constrained('modalities')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('student_enrollments', 'education_node_id')) {
                $table->foreignId('education_node_id')
                    ->nullable()
                    ->after('modality_id')
                    ->constrained('education_nodes')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('student_enrollments', 'year_level')) {
                $table->unsignedTinyInteger('year_level')
                    ->nullable()
                    ->after('education_node_id');
            }

            if (!Schema::hasColumn('student_enrollments', 'student_type')) {
                $table->string('student_type', 32)
                    ->nullable()
                    ->after('year_level')
                    ->comment('new|transferee|returnee|regular|irregular');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('student_enrollments', 'modality_id')) {
                $table->dropForeign(['modality_id']);
                $table->dropColumn('modality_id');
            }
            if (Schema::hasColumn('student_enrollments', 'education_node_id')) {
                $table->dropForeign(['education_node_id']);
                $table->dropColumn('education_node_id');
            }
            if (Schema::hasColumn('student_enrollments', 'year_level')) {
                $table->dropColumn('year_level');
            }
            if (Schema::hasColumn('student_enrollments', 'student_type')) {
                $table->dropColumn('student_type');
            }
        });
    }
};
