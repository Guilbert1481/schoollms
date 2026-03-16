<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_settings', function (Blueprint $table) {
            $table->integer('duration_minutes')->nullable()->after('availability_mode');
        });
    }

    public function down(): void
    {
        Schema::table('test_settings', function (Blueprint $table) {
            $table->dropColumn('duration_minutes');
        });
    }
};