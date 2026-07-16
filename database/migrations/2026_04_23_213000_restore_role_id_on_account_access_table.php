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
            if (! Schema::hasColumn('account_access', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('user_id');
            }
        });

        // Backfill role_id from role_snapshot <-> roles.name, scoped by school via users
        DB::statement('
            UPDATE account_access aa
            JOIN users u ON u.id = aa.user_id
            JOIN roles r ON r.school_id = u.school_id AND r.name = aa.role_snapshot
            SET aa.role_id = r.id
            WHERE aa.role_id IS NULL AND aa.role_snapshot IS NOT NULL
        ');

        // Fallback: backfill via users.role if role_snapshot missing
        DB::statement('
            UPDATE account_access aa
            JOIN users u ON u.id = aa.user_id
            JOIN roles r ON r.school_id = u.school_id AND r.name = u.role
            SET aa.role_id = r.id
            WHERE aa.role_id IS NULL
        ');

        // Add the FK only when it is not already present. The previous guard was
        // a try/catch INSIDE the Schema::table closure, but the closure merely
        // queues the command — the ALTER TABLE runs after it returns, so the
        // exception escaped the catch. On a fresh migrate (where 211700 already
        // added this FK and the intervening 212000 drop is now a no-op) that
        // surfaced as MySQL 1022 "Can't write; duplicate key" and aborted the
        // whole run. An explicit existence check is idempotent for both a fresh
        // build and a DB where the historical add→drop→restore already happened.
        if (! $this->foreignKeyExists('account_access', 'account_access_role_id_foreign')) {
            Schema::table('account_access', function (Blueprint $table) {
                $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
            });
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::selectOne('
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = ?
        ', [$table, $constraint, 'FOREIGN KEY']) !== null;
    }

    public function down(): void
    {
        Schema::table('account_access', function (Blueprint $table) {
            if (Schema::hasColumn('account_access', 'role_id')) {
                try {
                    $table->dropForeign(['role_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('role_id');
            }
        });
    }
};
