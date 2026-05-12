<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('scheduler_settings')) return;

        Schema::table('scheduler_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('scheduler_settings', 'min_days_per_week')) {
                $table->unsignedInteger('min_days_per_week')->default(1)->after('max_allowed_gap');
            }
            if (! Schema::hasColumn('scheduler_settings', 'max_days_per_week')) {
                $table->unsignedInteger('max_days_per_week')->default(3)->after('min_days_per_week');
            }
        });

        // Refresh sane defaults for existing rows (only when at legacy values).
        DB::table('scheduler_settings')
            ->where('max_session_hours', 3)
            ->update(['max_session_hours' => 2]);
        DB::table('scheduler_settings')
            ->where('max_subjects_per_day', 6)
            ->update(['max_subjects_per_day' => 5]);
        DB::table('scheduler_settings')
            ->where('max_allowed_gap', 60)
            ->update(['max_allowed_gap' => 30]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('scheduler_settings')) return;
        Schema::table('scheduler_settings', function (Blueprint $table) {
            if (Schema::hasColumn('scheduler_settings', 'max_days_per_week')) {
                $table->dropColumn('max_days_per_week');
            }
            if (Schema::hasColumn('scheduler_settings', 'min_days_per_week')) {
                $table->dropColumn('min_days_per_week');
            }
        });
    }
};
