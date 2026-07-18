<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3a — write-in (identification / matching) answers on top of the bubble
 * grading. Sheets gain a frozen written answer key; per-item rows widen so they
 * can hold a written answer + its correct text (they were sized for A–E only).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('omr_sheets', function (Blueprint $table) {
            $table->json('written_key')->nullable()->after('answer_key');
            $table->unsignedInteger('written_count')->default(0)->after('item_count');
        });

        Schema::table('omr_item_results', function (Blueprint $table) {
            $table->string('marked', 255)->nullable()->change();        // 'B' or a written answer
            $table->string('correct_label', 255)->nullable()->change(); // 'B' or the correct text
        });
    }

    public function down(): void
    {
        Schema::table('omr_sheets', function (Blueprint $table) {
            $table->dropColumn(['written_key', 'written_count']);
        });

        Schema::table('omr_item_results', function (Blueprint $table) {
            $table->string('marked', 32)->nullable()->change();
            $table->string('correct_label', 8)->nullable()->change();
        });
    }
};
