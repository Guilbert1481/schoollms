<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drive the installment schedule from the plan itself instead of the term:
     * the number of installments is computed from class_start_date → billing
     * end date at the chosen frequency, and each invoice/SOA falls on billing_day
     * of its period (e.g. the 8th of every month / every 3 months / every 6).
     */
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->date('class_start_date')->nullable()->after('frequencies');
            $table->date('class_end_date')->nullable()->after('class_start_date');
            $table->date('billing_end_date')->nullable()->after('class_end_date');
            $table->unsignedTinyInteger('billing_day')->nullable()->after('billing_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn(['class_start_date', 'class_end_date', 'billing_end_date', 'billing_day']);
        });
    }
};
