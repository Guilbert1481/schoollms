<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A plan's down payment can be expressed as a percentage of the bill or
        // as a flat peso amount. Add the type and rename the value column to a
        // type-agnostic name, widening it so it can hold a peso figure.
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->string('down_payment_type', 20)->default('percentage')->after('installments');
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->renameColumn('down_payment_percent', 'down_payment_value');
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->decimal('down_payment_value', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->decimal('down_payment_value', 5, 2)->default(0)->change();
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->renameColumn('down_payment_value', 'down_payment_percent');
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn('down_payment_type');
        });
    }
};
