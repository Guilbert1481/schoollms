<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_access', function (Blueprint $table) {
            if (!Schema::hasColumn('account_access', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('user_id');
            }
        });

        // Backfill role_id from role_snapshot <-> roles.name, scoped by school via users
        DB::statement("
            UPDATE account_access aa
            JOIN users u ON u.id = aa.user_id
            JOIN roles r ON r.school_id = u.school_id AND r.name = aa.role_snapshot
            SET aa.role_id = r.id
            WHERE aa.role_id IS NULL AND aa.role_snapshot IS NOT NULL
        ");

        // Fallback: backfill via users.role if role_snapshot missing
        DB::statement("
            UPDATE account_access aa
            JOIN users u ON u.id = aa.user_id
            JOIN roles r ON r.school_id = u.school_id AND r.name = u.role
            SET aa.role_id = r.id
            WHERE aa.role_id IS NULL
        ");

        Schema::table('account_access', function (Blueprint $table) {
            try {
                $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
            } catch (\Throwable $e) {
                // FK may already exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_access', function (Blueprint $table) {
            if (Schema::hasColumn('account_access', 'role_id')) {
                try { $table->dropForeign(['role_id']); } catch (\Throwable $e) {}
                $table->dropColumn('role_id');
            }
        });
    }
};
