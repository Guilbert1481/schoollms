<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified attendance store for ALL education levels. One row = one student's
 * attendance for one context on one date, regardless of how it was captured
 * (manual, QR scan, biometric, facial). Two scopes:
 *
 *   - daily   → basic ed homeroom: one time-in / time-out per student per day,
 *               keyed on the advisory `section_id`.
 *   - session → non-basic per-subject: time-in / time-out per class meeting,
 *               keyed on `class_id` (and optionally the concrete class_session).
 *
 * Every write funnels through App\Services\Attendance\AttendanceIngestionService,
 * which upserts on the natural key so a student never gets duplicate rows for the
 * same context/date. The composite unique index is a backstop for the session
 * case (class_id present); daily uniqueness is guaranteed by the funnel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->enum('scope', ['daily', 'session']);

            // Context — which of these is set depends on scope. Nullable so a
            // daily row carries no class, and a session row needs no section.
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('class_session_id')->nullable()->constrained('class_sessions')->nullOnDelete();

            // Academic context (denormalised for reporting / grade denominators).
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();

            $table->date('attendance_date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();

            $table->enum('status', ['present', 'absent', 'late', 'excused', 'half_day'])->default('present');
            $table->enum('method', ['manual', 'qr', 'biometric', 'facial'])->default('manual');

            $table->string('remarks')->nullable();

            // Who captured it (teacher for manual/QR; null for unattended devices).
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['school_id', 'attendance_date']);
            $table->index(['student_id', 'attendance_date']);
            $table->index(['class_id', 'attendance_date']);
            $table->index(['section_id', 'attendance_date']);

            // Backstop against duplicate session rows. The ingestion service is
            // the authoritative guard for all scopes (see class docblock).
            $table->unique(
                ['student_id', 'attendance_date', 'scope', 'class_id', 'section_id'],
                'attendance_records_natural_key_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
