<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects')) return;

        // Backfill is_active from active (in case any rows were only set on `active`)
        if (Schema::hasColumn('subjects', 'active') && Schema::hasColumn('subjects', 'is_active')) {
            DB::statement('UPDATE subjects SET is_active = active WHERE is_active IS NULL OR is_active <> active');
        }

        if (Schema::hasColumn('subjects', 'active')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subjects') && ! Schema::hasColumn('subjects', 'active')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('is_active');
            });

            if (Schema::hasColumn('subjects', 'is_active')) {
                DB::statement('UPDATE subjects SET active = is_active');
            }
        }
    }
};
