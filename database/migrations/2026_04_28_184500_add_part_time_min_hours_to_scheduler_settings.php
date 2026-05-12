<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('scheduler_settings')) return;

        Schema::table('scheduler_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('scheduler_settings', 'part_time_min_hours_per_day')) {
                $table->decimal('part_time_min_hours_per_day', 4, 2)
                    ->default(1)
                    ->after('prioritize_full_time');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('scheduler_settings')) return;

        Schema::table('scheduler_settings', function (Blueprint $table) {
            if (Schema::hasColumn('scheduler_settings', 'part_time_min_hours_per_day')) {
                $table->dropColumn('part_time_min_hours_per_day');
            }
        });
    }
};
