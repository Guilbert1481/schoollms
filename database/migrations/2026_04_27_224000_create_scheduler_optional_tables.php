<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) is_primary on teacher_subjects
        if (Schema::hasTable('teacher_subjects') && ! Schema::hasColumn('teacher_subjects', 'is_primary')) {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->boolean('is_primary')->default(false)->after('subject_id');
            });
        }

        // 2) teacher_preferences
        if (! Schema::hasTable('teacher_preferences')) {
            Schema::create('teacher_preferences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id');
                $table->string('preferred_block', 16)->nullable(); // morning|afternoon|any
                $table->decimal('max_hours_per_day', 4, 2)->nullable();
                $table->decimal('max_hours_per_week', 5, 2)->nullable();
                $table->timestamps();
                $table->unique('teacher_id');
            });
        }

        // 3) scheduler_settings (singleton-style; one row per school)
        if (! Schema::hasTable('scheduler_settings')) {
            Schema::create('scheduler_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->unique();
                $table->decimal('min_session_hours', 4, 2)->default(1);
                $table->decimal('max_session_hours', 4, 2)->default(3);
                $table->unsignedInteger('max_subjects_per_day')->default(6);
                $table->decimal('max_hours_per_day', 4, 2)->default(8);
                $table->decimal('max_hours_per_week', 5, 2)->default(40);
                $table->unsignedInteger('max_allowed_gap')->default(60); // minutes
                $table->boolean('allow_gaps')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teacher_subjects') && Schema::hasColumn('teacher_subjects', 'is_primary')) {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->dropColumn('is_primary');
            });
        }
        Schema::dropIfExists('teacher_preferences');
        Schema::dropIfExists('scheduler_settings');
    }
};
