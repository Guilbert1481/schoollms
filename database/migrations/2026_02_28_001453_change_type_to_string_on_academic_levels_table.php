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
    Schema::table('academic_levels', function (Blueprint $table) {
        // This converts the ENUM to a flexible string column
        $table->string('type')->default('basic')->change(); 
    });
}

public function down(): void
{
    Schema::table('academic_levels', function (Blueprint $table) {
        // This reverts it back to the original ENUM list from your HeidiSQL
        $table->enum('type', ['basic', 'higher', 'training', 'review'])->change();
    });
}
};
