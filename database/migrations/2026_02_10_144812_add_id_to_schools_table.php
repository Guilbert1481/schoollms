<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Check if the column exists first to avoid errors
            if (!Schema::hasColumn('schools', 'id')) {
                $table->id()->first(); // Adds auto-incrementing ID as the first column
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('id'); // Allows you to rollback if needed
        });
    }
};