<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (! Schema::hasColumn('sections', 'status')) {
                // draft | published | archived
                $table->string('status', 20)->default('draft')->after('is_active');
            }
        });

        // Backfill: any existing active section is treated as already published
        // so we don't accidentally block enrolment for live data.
        DB::table('sections')->where('is_active', 1)->update(['status' => 'published']);
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
