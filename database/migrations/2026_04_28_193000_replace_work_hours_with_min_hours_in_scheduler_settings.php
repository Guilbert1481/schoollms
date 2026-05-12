<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('scheduler_settings')) return;

        Schema::table('scheduler_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('scheduler_settings', 'teacher_min_hours_per_day')) {
                $table->decimal('teacher_min_hours_per_day', 4, 2)
                    ->default(1)
                    ->after('teacher_work_days_per_week');
            }

            if (Schema::hasColumn('scheduler_settings', 'teacher_work_hours_per_day')) {
                $table->dropColumn('teacher_work_hours_per_day');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('scheduler_settings')) return;

        Schema::table('scheduler_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('scheduler_settings', 'teacher_work_hours_per_day')) {
                $table->decimal('teacher_work_hours_per_day', 4, 2)
                    ->default(9)
                    ->after('teacher_work_days_per_week');
            }

            if (Schema::hasColumn('scheduler_settings', 'teacher_min_hours_per_day')) {
                $table->dropColumn('teacher_min_hours_per_day');
            }
        });
    }
};
