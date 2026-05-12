<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('school_modules', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('is_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_modules', function (Blueprint $table) {
            if (Schema::hasColumn('school_modules', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};
