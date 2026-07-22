<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part C — a per-test "require fullscreen" lockdown flag for online tests.
 * Soft proctoring: the take screen gates the test behind fullscreen and logs exits
 * (reusing the Phase-5 integrity log), but a browser can't be truly locked down, so
 * this is a deterrent + audit signal, not a cage. Defaults off; F2F tests ignore it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_settings', function (Blueprint $table) {
            $table->boolean('require_fullscreen')->default(false)->after('shuffle_mcq_choices');
        });
    }

    public function down(): void
    {
        Schema::table('test_settings', function (Blueprint $table) {
            $table->dropColumn('require_fullscreen');
        });
    }
};
