<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {

            // Does this program require admission test?
            $table->boolean('requires_admission_test')
                  ->default(false)
                  ->after('name');

            // Minimum passing score (percentage)
            $table->integer('admission_passing_score')
                  ->nullable()
                  ->after('requires_admission_test');

            // Maximum allowed attempts
            $table->integer('admission_max_attempts')
                  ->default(1)
                  ->after('admission_passing_score');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn([
                'requires_admission_test',
                'admission_passing_score',
                'admission_max_attempts',
            ]);
        });
    }
};
