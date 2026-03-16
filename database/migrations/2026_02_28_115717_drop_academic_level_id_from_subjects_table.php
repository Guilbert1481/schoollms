<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Drop foreign key first if it exists
            $table->dropForeign(['academic_level_id']);

            // Then drop column
            $table->dropColumn('academic_level_id');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('academic_level_id')
                  ->constrained()
                  ->cascadeOnDelete();
        });
    }
};