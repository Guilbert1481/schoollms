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
        foreach (['academic_years', 'terms'] as $tbl) {
            if (!Schema::hasColumn($tbl, 'education_level')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->string('education_level', 20)
                        ->default('higher_ed')
                        ->after('school_id');
                    $table->index(['school_id', 'education_level']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['academic_years', 'terms'] as $tbl) {
            if (Schema::hasColumn($tbl, 'education_level')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropIndex(['school_id', 'education_level']);
                    $table->dropColumn('education_level');
                });
            }
        }
    }
};
