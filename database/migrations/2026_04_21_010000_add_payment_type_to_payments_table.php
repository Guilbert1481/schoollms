<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type', 50)->default('miscellaneous')->after('payment_method');
                $table->index('payment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_type')) {
                $table->dropIndex(['payment_type']);
                $table->dropColumn('payment_type');
            }
        });
    }
};
