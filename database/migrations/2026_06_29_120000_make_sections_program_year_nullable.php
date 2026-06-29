<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            // Basic-Education sections have no program; allow creating sections
            // (e.g. via the Student Ledgers import) without a program/year level.
            $table->unsignedBigInteger('program_id')->nullable()->change();
            $table->integer('year_level')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable(false)->change();
            $table->integer('year_level')->nullable(false)->change();
        });
    }
};
