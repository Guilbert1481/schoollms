<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('event_attendances', function (Blueprint $table) {
            $table->time('time_in_at')->nullable()->after('status');
            $table->time('time_out_at')->nullable()->after('time_in_at');
            $table->string('capture_source', 20)->default('manual')->after('time_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_attendances', function (Blueprint $table) {
            $table->dropColumn(['time_in_at', 'time_out_at', 'capture_source']);
        });
    }
};
