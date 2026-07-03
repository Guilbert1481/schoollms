<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Term names are unique per academic year — but now also per education level,
     * so concurrent levels can each have e.g. a "1st Semester 2026-2027" term
     * (Undergraduate AND Graduate) without colliding. Same name within the same
     * level is still rejected.
     */
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropUnique('terms_unique_name_per_year');
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->unique(['school_id', 'academic_year_id', 'name', 'education_node_id'], 'terms_unique_name_per_year');
        });
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropUnique('terms_unique_name_per_year');
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->unique(['school_id', 'academic_year_id', 'name'], 'terms_unique_name_per_year');
        });
    }
};
