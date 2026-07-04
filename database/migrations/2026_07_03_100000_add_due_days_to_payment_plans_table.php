<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grace window between a bill's billing day and its due date:
     *   due date = billing day + due_days   (billing_day 8 + due_days 5 → due 13th).
     * Applies to every scheduled row — cash, down payment and each installment.
     * Null / 0 preserves the old behaviour (due on the billing day itself).
     */
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('due_days')->nullable()->after('billing_day');
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn('due_days');
        });
    }
};
