<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {

            $table->date('start_date')->nullable()->after('role_id');
            $table->date('end_date')->nullable()->after('start_date');

            $table->boolean('is_active')->default(true)->after('end_date');

            $table->enum('assignment_type', [
                'permanent',
                'temporary',
                'acting',
                'concurrent',
                'part_time',
                'leave',
                'inactive'
            ])->default('permanent')->after('is_active');

            $table->string('remarks')->nullable()->after('assignment_type');
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