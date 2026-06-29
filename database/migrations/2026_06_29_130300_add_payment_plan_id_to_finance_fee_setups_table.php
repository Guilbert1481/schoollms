<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_fee_setups', function (Blueprint $table) {
            $table->foreignId('payment_plan_id')->nullable()->after('program_id')
                ->constrained('payment_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_fee_setups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_plan_id');
        });
    }
};
