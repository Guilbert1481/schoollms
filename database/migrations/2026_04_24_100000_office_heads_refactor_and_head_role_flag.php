<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // Drop office_heads table if exists
        if (Schema::hasTable('office_heads')) {
            Schema::drop('office_heads');
        }

        // Drop office_head_id from offices, add head_role_id
        Schema::table('offices', function (Blueprint $table) {
            if (Schema::hasColumn('offices', 'office_head_id')) {
                $table->dropForeign(['office_head_id']);
                $table->dropColumn('office_head_id');
            }
            if (!Schema::hasColumn('offices', 'head_role_id')) {
                $table->unsignedBigInteger('head_role_id')->nullable()->after('office_type_id');
                $table->foreign('head_role_id')->references('id')->on('roles')->nullOnDelete();
            }
        });

        // Add is_head_role to roles
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'is_head_role')) {
                $table->boolean('is_head_role')->default(false)->after('name');
            }
        });
    }

    public function down()
    {
        // Restore office_head_id, remove head_role_id
        Schema::table('offices', function (Blueprint $table) {
            if (Schema::hasColumn('offices', 'head_role_id')) {
                $table->dropForeign(['head_role_id']);
                $table->dropColumn('head_role_id');
            }
            if (!Schema::hasColumn('offices', 'office_head_id')) {
                $table->unsignedBigInteger('office_head_id')->nullable()->after('office_type_id');
                $table->foreign('office_head_id')->references('id')->on('users')->nullOnDelete();
            }
        });

        // Remove is_head_role from roles
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'is_head_role')) {
                $table->dropColumn('is_head_role');
            }
        });

        // Cannot restore office_heads table (data lost)
    }
};