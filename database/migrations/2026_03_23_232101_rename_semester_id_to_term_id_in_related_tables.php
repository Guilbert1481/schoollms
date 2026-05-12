<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign keys that reference semesters.id
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign('classes_semester_id_foreign');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign('sections_semester_id_foreign');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropForeign('student_enrollments_semester_id_foreign');
        });

        // Rename columns semester_id -> term_id
        Schema::table('classes', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->change(); // ensure type
        });
        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->change();
        });
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->change();
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->renameColumn('semester_id', 'term_id');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->renameColumn('semester_id', 'term_id');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->renameColumn('semester_id', 'term_id');
        });

        // Non‑FK but semantic: enrollments.semester_id -> term_id
        if (Schema::hasColumn('enrollments', 'semester_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->renameColumn('semester_id', 'term_id');
            });
        }

        // Recreate foreign keys pointing to terms(id)
        Schema::table('classes', function (Blueprint $table) {
            $table->foreign('term_id')
                ->references('id')
                ->on('terms')
                ->onDelete('cascade');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->foreign('term_id')
                ->references('id')
                ->on('terms')
                ->onDelete('cascade');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->foreign('term_id')
                ->references('id')
                ->on('terms')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Drop new FKs
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign('classes_term_id_foreign');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign('sections_term_id_foreign');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropForeign('student_enrollments_term_id_foreign');
        });

        // Rename columns term_id -> semester_id
        Schema::table('classes', function (Blueprint $table) {
            $table->renameColumn('term_id', 'semester_id');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->renameColumn('term_id', 'semester_id');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->renameColumn('term_id', 'semester_id');
        });

        if (Schema::hasColumn('enrollments', 'term_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->renameColumn('term_id', 'semester_id');
            });
        }

        // Recreate old FKs to semesters(id)
        Schema::table('classes', function (Blueprint $table) {
            $table->foreign('semester_id')
                ->references('id')
                ->on('semesters')
                ->onDelete('cascade');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->foreign('semester_id')
                ->references('id')
                ->on('semesters')
                ->onDelete('cascade');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->foreign('semester_id')
                ->references('id')
                ->on('semesters')
                ->onDelete('cascade');
        });
    }
};
