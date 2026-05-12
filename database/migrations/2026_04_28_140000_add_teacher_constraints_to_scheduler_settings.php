<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('scheduler_settings')) return;

        Schema::table('scheduler_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('scheduler_settings', 'teacher_max_hours_per_week')) {
                $table->unsignedInteger('teacher_max_hours_per_week')->default(24)->after('max_days_per_week');
            }
            if (! Schema::hasColumn('scheduler_settings', 'teacher_max_hours_per_day')) {
                $table->unsignedInteger('teacher_max_hours_per_day')->default(5)->after('teacher_max_hours_per_week');
            }
            if (! Schema::hasColumn('scheduler_settings', 'teacher_work_days_per_week')) {
                $table->unsignedInteger('teacher_work_days_per_week')->default(5)->after('teacher_max_hours_per_day');
            }
            if (! Schema::hasColumn('scheduler_settings', 'prioritize_full_time')) {
                $table->boolean('prioritize_full_time')->default(true)->after('teacher_work_days_per_week');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('scheduler_settings')) return;

        Schema::table('scheduler_settings', function (Blueprint $table) {
            foreach ([
                'prioritize_full_time',
                'teacher_work_days_per_week',
                'teacher_max_hours_per_day',
                'teacher_max_hours_per_week',
            ] as $col) {
                if (Schema::hasColumn('scheduler_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
