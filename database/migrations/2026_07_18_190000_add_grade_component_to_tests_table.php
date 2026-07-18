<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a test feed the gradebook, the same way homework already does.
     *
     * Tagging a test with a grade component means every OMR-scanned score for it
     * rolls into that component automatically (TestComponentFeed) instead of the
     * teacher re-keying scores by hand. Both columns are nullable: an untagged test
     * is simply not fed, which keeps every existing test behaving exactly as before.
     *
     * `grading_period` mirrors homework — basic education records a score per period,
     * higher ed does not use it.
     */
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->foreignId('grade_component_id')->nullable()->constrained('grade_components')->nullOnDelete();
            $table->unsignedTinyInteger('grading_period')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropForeign(['grade_component_id']);
            $table->dropColumn(['grade_component_id', 'grading_period']);
        });
    }
};
