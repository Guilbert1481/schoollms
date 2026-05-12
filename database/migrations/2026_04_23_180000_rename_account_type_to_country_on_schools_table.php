<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('country');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->enum('account_type', ['local', 'international'])
                  ->default('local')
                  ->after('type');
        });
    }
};
