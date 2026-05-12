<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('academic_years', 'status')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->string('status', 20)->default('upcoming')->after('is_active');
            });

            // Backfill from is_active
            DB::table('academic_years')->where('is_active', 1)->update(['status' => 'active']);
            DB::table('academic_years')->where('is_active', 0)->update(['status' => 'upcoming']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('academic_years', 'status')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
