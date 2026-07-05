<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auto-send bookkeeping for Statements of Account (mirrors invoices.emailed_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statement_of_accounts', function (Blueprint $table) {
            $table->timestamp('emailed_at')->nullable()->after('generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('statement_of_accounts', function (Blueprint $table) {
            $table->dropColumn('emailed_at');
        });
    }
};
