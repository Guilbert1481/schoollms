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
        Schema::table('terms', function (Blueprint $table) {

    // Drop old constraint
    $table->dropUnique('semesters_school_id_academic_year_term_unique');

    // Add new constraint
    $table->unique([
        'school_id',
        'academic_year_id',
        'name'
    ], 'terms_unique_name_per_year');

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
