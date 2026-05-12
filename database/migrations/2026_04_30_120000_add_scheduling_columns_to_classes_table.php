<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original `classes` migration only stored `max_students` + `is_active`,
 * but the higher-ed irregular enrolment flow (and the ClassModel fillable)
 * expect richer scheduling metadata: a per-section class code, a room, a
 * human-readable schedule string, plus `capacity` and `is_open`.
 *
 * We add them as nullable columns so existing rows keep working and the
 * irregular wizard can render the class catalogue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'code')) {
                $table->string('code', 64)->nullable()->after('section_id');
            }
            if (!Schema::hasColumn('classes', 'room')) {
                $table->string('room', 64)->nullable()->after('code');
            }
            if (!Schema::hasColumn('classes', 'schedule')) {
                $table->string('schedule', 191)->nullable()->after('room');
            }
            if (!Schema::hasColumn('classes', 'capacity')) {
                $table->unsignedInteger('capacity')->nullable()->after('schedule');
            }
            if (!Schema::hasColumn('classes', 'is_open')) {
                $table->boolean('is_open')->default(true)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            foreach (['is_open', 'capacity', 'schedule', 'room', 'code'] as $col) {
                if (Schema::hasColumn('classes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
