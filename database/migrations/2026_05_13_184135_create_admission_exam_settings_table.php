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
        Schema::create('admission_exam_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                  ->unique()
                  ->constrained('schools')
                  ->cascadeOnDelete();

            // Who must take an entrance / admission exam
            $table->boolean('require_for_new_student')->default(false); // general exam
            $table->boolean('require_for_transferee')->default(false);  // program exam
            $table->boolean('require_for_returnee')->default(false);
            $table->boolean('require_for_shiftee')->default(false);

            // diagnostic_only       = result is informational / placement only, never blocks admission
            // admission_requirement = must reach passing_score to be admitted
            $table->enum('exam_purpose', ['diagnostic_only', 'admission_requirement'])
                  ->default('diagnostic_only');

            // Scoring
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->unsignedSmallInteger('passing_score')->nullable(); // required when exam_purpose = admission_requirement

            // Attempts & retakes
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->unsignedSmallInteger('retake_cooldown_days')->nullable();

            // Result validity (months) — re-test required after this if expired
            $table->unsignedSmallInteger('result_validity_months')->nullable();

            // Workflow toggles
            $table->boolean('allow_program_head_waiver')->default(true);
            $table->boolean('notify_applicant_on_schedule')->default(true);
            $table->boolean('auto_assess_after_pass')->default(false);

            $table->text('instructions')->nullable(); // shown to applicants

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_exam_settings');
    }
};
