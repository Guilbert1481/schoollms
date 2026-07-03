<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A payment plan is now a named offering that bundles the billing
     * frequencies the school allows and up to three independently-enabled
     * options a guardian can choose from:
     *   1. Cash (full payment) with an optional discount,
     *   2. Down payment + installment (DP reuses down_payment_type/value),
     *   3. Installment + interest.
     * The installment count is derived from the chosen frequency, so the old
     * fixed `installments` column is no longer the driver (kept for back-compat).
     */
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->json('frequencies')->nullable()->after('name');

            $table->boolean('cash_enabled')->default(false)->after('frequencies');
            $table->string('cash_discount_type', 20)->nullable()->after('cash_enabled');
            $table->decimal('cash_discount_value', 12, 2)->default(0)->after('cash_discount_type');

            $table->boolean('dp_enabled')->default(false)->after('down_payment_value');

            $table->boolean('interest_enabled')->default(false)->after('dp_enabled');
            $table->decimal('interest_rate', 5, 2)->default(0)->after('interest_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn([
                'frequencies',
                'cash_enabled',
                'cash_discount_type',
                'cash_discount_value',
                'dp_enabled',
                'interest_enabled',
                'interest_rate',
            ]);
        });
    }
};
