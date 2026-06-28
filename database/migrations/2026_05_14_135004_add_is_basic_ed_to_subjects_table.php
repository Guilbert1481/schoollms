<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'is_basic_ed')) {
                $table->boolean('is_basic_ed')->default(false)->after('is_active');
                $table->index('is_basic_ed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'is_basic_ed')) {
                $table->dropIndex(['is_basic_ed']);
                $table->dropColumn('is_basic_ed');
            }
        });
    }
};
