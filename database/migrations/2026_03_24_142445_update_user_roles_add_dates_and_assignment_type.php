<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {

            // If start_date does not exist yet
            if (!Schema::hasColumn('user_roles', 'start_date')) {
                $table->date('start_date')->after('role_id');
            }

            // If end_date does not exist yet
            if (!Schema::hasColumn('user_roles', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            // If is_active does not exist
            if (!Schema::hasColumn('user_roles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('end_date');
            }

            // Assignment type
            if (!Schema::hasColumn('user_roles', 'assignment_type')) {
                $table->enum('assignment_type', [
                    'permanent',
                    'temporary',
                    'acting',
                    'concurrent',
                    'part_time',
                    'leave',
                    'inactive'
                ])->default('permanent')->after('is_active');
            }

            // Remarks
            if (!Schema::hasColumn('user_roles', 'remarks')) {
                $table->string('remarks')->nullable()->after('assignment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropColumn([
                'start_date',
                'end_date',
                'is_active',
                'assignment_type',
                'remarks'
            ]);
        });
    }
};