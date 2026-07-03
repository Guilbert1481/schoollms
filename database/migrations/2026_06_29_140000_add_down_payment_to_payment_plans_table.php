<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            // Percentage of the total paid upfront; the remainder is spread over
            // `installments`. 0 = no down payment (pure installments / full payment).
            $table->decimal('down_payment_percent', 5, 2)->default(0)->after('installments');
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn('down_payment_percent');
        });
    }
};
