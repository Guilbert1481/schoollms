<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->change();
            $table->string('account_name')->nullable()->change();
            $table->string('account_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->string('bank_name')->nullable(false)->change();
            $table->string('account_name')->nullable(false)->change();
            $table->string('account_number')->nullable(false)->change();
        });
    }
};
