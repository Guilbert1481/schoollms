<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_settings', function (Blueprint $table) {

            // Session name
            if (!Schema::hasColumn('enrollment_settings', 'name')) {
                $table->string('name')->after('id');
            }

            // Enrollment type (Regular / Special)
            if (!Schema::hasColumn('enrollment_settings', 'enrollment_type_id')) {
                $table->foreignId('enrollment_type_id')
                      ->nullable()
                      ->after('name')
                      ->constrained()
                      ->nullOnDelete();
            }

            // Active flag
            if (!Schema::hasColumn('enrollment_settings', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('end_date');
            }

            // Updated by
            if (!Schema::hasColumn('enrollment_settings', 'updated_by')) {
                $table->foreignId('updated_by')
                      ->nullable()
                      ->after('created_by')
                      ->constrained('users')
                      ->nullOnDelete();
            }

        });
    }

    public function down(): void
    {
        Schema::table('enrollment_settings', function (Blueprint $table) {

            if (Schema::hasColumn('enrollment_settings', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            }

            if (Schema::hasColumn('enrollment_settings', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('enrollment_settings', 'enrollment_type_id')) {
                $table->dropForeign(['enrollment_type_id']);
                $table->dropColumn('enrollment_type_id');
            }

            if (Schema::hasColumn('enrollment_settings', 'name')) {
                $table->dropColumn('name');
            }

        });
    }
};