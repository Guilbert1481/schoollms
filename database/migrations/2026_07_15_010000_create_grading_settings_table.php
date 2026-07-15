<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-education-level grading configuration (fresh, level-scoped — supersedes
 * the never-wired grading_systems/class_grades scaffolding). One grading_settings
 * row per (school, academic level) holds the scale, passing mark, and the
 * attendance weight; grade_components holds the weighted components the grade is
 * built from (e.g. Written Work / Performance Tasks / Quarterly Assessment).
 *
 * The Principal configures basic-ed levels, the Dean higher-ed levels. The
 * engine that consumes these weights is a later phase; this is the config only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_level_id')->constrained('academic_levels')->cascadeOnDelete();

            $table->enum('scale_type', ['percentage', 'gpa', 'letter', 'pass_fail'])->default('percentage');
            $table->decimal('passing_mark', 5, 2)->default(75.00);

            // How much of the final grade attendance is worth (0 = not counted).
            // The remaining weight is split across grade_components.
            $table->decimal('attendance_weight', 5, 2)->default(0.00);

            $table->timestamps();

            $table->unique(['school_id', 'academic_level_id']);
        });

        Schema::create('grade_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('grading_setting_id')->constrained('grading_settings')->cascadeOnDelete();

            $table->string('name', 100);
            $table->decimal('weight', 5, 2)->default(0.00);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('grading_setting_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_components');
        Schema::dropIfExists('grading_settings');
    }
};
