<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auto-send bookkeeping: when the invoice PDF was emailed to the student and
 * guardians. NULL = never sent; the daily sender only picks NULL rows, so a
 * stamped invoice can never be emailed twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('emailed_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('emailed_at');
        });
    }
};
