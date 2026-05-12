<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->string('category', 50)
                ->default('training')
                ->after('certificate_type');
        });

        // Backfill for safety if legacy rows exist without value.
        DB::table('certificate_templates')
            ->whereNull('category')
            ->update(['category' => 'training']);
    }

    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
