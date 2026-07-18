<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2a — OMR grading backbone.
 *
 *   omr_sheets         one immutable sheet per (test, student): the frozen
 *                      answer-key snapshot + bubble map printed for that student.
 *   omr_scan_attempts  every scan/manual submission — an append-only audit log;
 *                      results are never silently overwritten.
 *   omr_results        the current authoritative graded result per sheet, always
 *                      pointing at the attempt that produced it.
 *   omr_item_results   per-item breakdown for the current result.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('omr_sheets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('layout_version', 16)->default('v1');
            $table->string('token', 64)->unique();          // QR payload → this sheet
            $table->json('answer_key');                      // frozen bubble map (see OmrSheetSnapshotService)
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('max_score')->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['test_id', 'student_id']);       // one immutable sheet per student per test
            $table->index('school_id');
            $table->index('section_id');
        });

        Schema::create('omr_scan_attempts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('omr_sheet_id');
            $table->unsignedBigInteger('scanned_by')->nullable(); // user who scanned/entered
            $table->string('source', 16)->default('manual');      // manual | camera
            $table->json('marked_answers');                       // [{n, marks:['B'] | ['A','C'] | []}]
            $table->json('confidence')->nullable();               // per-item confidence (Phase 2b)
            $table->json('meta')->nullable();                     // scan metadata (device, timings…)
            $table->json('outcome')->nullable();                  // computed result snapshot at attempt time (audit)
            $table->timestamps();

            $table->foreign('omr_sheet_id')->references('id')->on('omr_sheets')->cascadeOnDelete();
            $table->index('school_id');
            $table->index(['omr_sheet_id', 'created_at']);
        });

        Schema::create('omr_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('omr_sheet_id');
            $table->unsignedBigInteger('scan_attempt_id');   // attempt that produced this result
            $table->unsignedInteger('raw_score')->default(0);
            $table->unsignedInteger('max_score')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('incorrect_count')->default(0);
            $table->unsignedInteger('blank_count')->default(0);
            $table->unsignedInteger('multiple_count')->default(0);
            $table->boolean('is_override')->default(false);
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->foreign('omr_sheet_id')->references('id')->on('omr_sheets')->cascadeOnDelete();
            $table->unique('omr_sheet_id');                  // one current result per sheet
            $table->index('school_id');
        });

        Schema::create('omr_item_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('omr_result_id');
            $table->unsignedInteger('item_number');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->string('marked', 32)->nullable();        // 'B', 'A,C', or null (blank)
            $table->string('correct_label', 8)->nullable();  // 'B'
            $table->string('outcome', 16);                   // correct|incorrect|blank|multiple
            $table->timestamps();

            $table->foreign('omr_result_id')->references('id')->on('omr_results')->cascadeOnDelete();
            $table->index(['omr_result_id', 'item_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('omr_item_results');
        Schema::dropIfExists('omr_results');
        Schema::dropIfExists('omr_scan_attempts');
        Schema::dropIfExists('omr_sheets');
    }
};
