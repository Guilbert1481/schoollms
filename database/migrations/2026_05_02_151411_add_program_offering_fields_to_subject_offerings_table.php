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
        Schema::table('subject_offerings', function (Blueprint $table) {
            $table->foreignId('program_id')
                  ->nullable()
                  ->after('term_id')
                  ->constrained('programs')
                  ->nullOnDelete();

            $table->unsignedTinyInteger('year_level')->nullable()->after('program_id');

            $table->boolean('is_open')->default(true)->after('year_level');
            $table->boolean('is_for_irregular')->default(true)->after('is_open');

            // Composite uniqueness (term + subject + program + year_level)
            $table->unique(
                ['term_id', 'subject_id', 'program_id', 'year_level'],
                'subj_off_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_offerings', function (Blueprint $table) {
            $table->dropUnique('subj_off_unique');
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn(['year_level', 'is_open', 'is_for_irregular']);
        });
    }
};
