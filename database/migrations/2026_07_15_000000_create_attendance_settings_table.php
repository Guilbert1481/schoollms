<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-education-level attendance configuration. One row per (school, academic
 * level). The Principal owns the basic-ed levels; the Dean owns the higher-ed
 * levels (routes are role-gated, the band is fixed by the route).
 *
 * Governs how attendance is captured and how a raw time-in becomes a status.
 * The "attendance as a graded component + its weight" lives in the grading
 * config, not here — this table is purely capture + status rules + the basic-ed
 * denominator for an attendance rate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_level_id')->constrained('academic_levels')->cascadeOnDelete();

            // Basic ed defaults to daily homeroom; higher ed to per-subject session.
            $table->enum('capture_mode', ['daily', 'session']);

            // Which capture methods are enabled for this level.
            $table->boolean('allow_manual')->default(true);
            $table->boolean('allow_qr')->default(false);
            $table->boolean('allow_biometric')->default(false);
            $table->boolean('allow_facial')->default(false);

            // Status rules: a time-in after (late_after + grace) counts as late.
            $table->time('late_after')->nullable();
            $table->unsignedSmallInteger('grace_minutes')->default(0);
            $table->boolean('half_day_enabled')->default(false);

            // Basic-ed attendance-rate denominator (no school calendar yet). Null
            // for session mode, where the denominator is the count of class sessions.
            $table->unsignedSmallInteger('expected_days_per_period')->nullable();

            // QR rotating-token interval used by the live-attendance screen later.
            $table->unsignedSmallInteger('qr_rotation_seconds')->default(10);

            $table->timestamps();

            $table->unique(['school_id', 'academic_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
