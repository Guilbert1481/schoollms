<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Audit the submitted written (identification/matching) answers per scan attempt. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('omr_scan_attempts', function (Blueprint $table) {
            $table->json('written_answers')->nullable()->after('marked_answers');
        });
    }

    public function down(): void
    {
        Schema::table('omr_scan_attempts', function (Blueprint $table) {
            $table->dropColumn('written_answers');
        });
    }
};
