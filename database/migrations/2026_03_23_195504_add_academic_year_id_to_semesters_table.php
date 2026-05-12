<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')->nullable()->after('school_id');
            $table->index(['school_id', 'academic_year_id'], 'semesters_school_academic_year_idx');
        });
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropIndex('semesters_school_academic_year_idx');
            $table->dropColumn('academic_year_id');
        });
    }
};