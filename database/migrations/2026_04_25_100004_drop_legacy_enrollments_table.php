<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidate enrolment storage onto student_enrollments.
 *
 * The legacy `enrollments` table is being retired in favour of `student_enrollments`,
 * which already pivots to student_enrollment_subjects. This migration:
 *   1. Removes orphan rows in enrollment_logs / enrollment_documents that referenced
 *      the legacy table (they cannot be safely re-pointed because their parent IDs
 *      were issued by `enrollments`, not `student_enrollments`).
 *   2. Drops the foreign keys that pointed at `enrollments`.
 *   3. Drops the `enrollments` table.
 *   4. Re-creates the foreign keys against `student_enrollments`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- 1. Clean orphans before changing constraints -----------------
        if (Schema::hasTable('enrollment_logs')) {
            DB::table('enrollment_logs')->delete();
        }

        if (Schema::hasTable('enrollment_documents')) {
            DB::table('enrollment_documents')->delete();
        }

        // --- 2. Drop existing FKs pointing at `enrollments` ---------------
        if (Schema::hasTable('enrollment_logs')
            && Schema::hasColumn('enrollment_logs', 'enrollment_id')) {

            Schema::table('enrollment_logs', function (Blueprint $table) {
                try {
                    $table->dropForeign(['enrollment_id']);
                } catch (\Throwable $e) {
                    // FK may not exist under the conventional name; ignore
                }
            });
        }

        if (Schema::hasTable('enrollment_documents')
            && Schema::hasColumn('enrollment_documents', 'enrollment_id')) {

            Schema::table('enrollment_documents', function (Blueprint $table) {
                try {
                    $table->dropForeign(['enrollment_id']);
                } catch (\Throwable $e) {
                    // FK may not exist under the conventional name; ignore
                }
            });
        }

        // --- 3. Drop the legacy table -------------------------------------
        Schema::dropIfExists('enrollments');

        // --- 4. Repoint FKs to student_enrollments ------------------------
        if (Schema::hasTable('enrollment_logs')
            && Schema::hasColumn('enrollment_logs', 'enrollment_id')) {

            Schema::table('enrollment_logs', function (Blueprint $table) {
                $table->foreign('enrollment_id')
                      ->references('id')
                      ->on('student_enrollments')
                      ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('enrollment_documents')
            && Schema::hasColumn('enrollment_documents', 'enrollment_id')) {

            Schema::table('enrollment_documents', function (Blueprint $table) {
                $table->foreign('enrollment_id')
                      ->references('id')
                      ->on('student_enrollments')
                      ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Drop the new FKs
        if (Schema::hasTable('enrollment_logs')) {
            Schema::table('enrollment_logs', function (Blueprint $table) {
                try {
                    $table->dropForeign(['enrollment_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
            });
        }

        if (Schema::hasTable('enrollment_documents')) {
            Schema::table('enrollment_documents', function (Blueprint $table) {
                try {
                    $table->dropForeign(['enrollment_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
            });
        }

        // Recreate the legacy enrollments table (minimum viable shape so the
        // child FK constraints below have something to bind to).
        if (!Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('program_id')->constrained()->restrictOnDelete();
                $table->foreignId('term_id')->constrained()->restrictOnDelete();
                $table->foreignId('campus_id')->constrained()->restrictOnDelete();
                $table->string('enrollment_type');
                $table->string('enrollment_status')->default('draft');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('enrollment_logs')) {
            Schema::table('enrollment_logs', function (Blueprint $table) {
                $table->foreign('enrollment_id')
                      ->references('id')
                      ->on('enrollments')
                      ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('enrollment_documents')) {
            Schema::table('enrollment_documents', function (Blueprint $table) {
                $table->foreign('enrollment_id')
                      ->references('id')
                      ->on('enrollments')
                      ->cascadeOnDelete();
            });
        }
    }
};
