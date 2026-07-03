<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the "scholarship grants" exam purpose + a JSON list of score→scholarship
     * bands (percent discount, coverage, minimum score) configured on the Admission
     * Exam settings tab. Applied to the enrolment Financial Assessment step.
     */
    public function up(): void
    {
        Schema::table('admission_exam_settings', function (Blueprint $table) {
            $table->json('scholarship_bands')->nullable()->after('instructions');
        });

        DB::statement("ALTER TABLE admission_exam_settings MODIFY exam_purpose ENUM('diagnostic_only','admission_requirement','scholarship_grants') NOT NULL DEFAULT 'diagnostic_only'");
    }

    public function down(): void
    {
        // Revert any scholarship_grants rows to diagnostic before shrinking the enum.
        DB::table('admission_exam_settings')->where('exam_purpose', 'scholarship_grants')->update(['exam_purpose' => 'diagnostic_only']);
        DB::statement("ALTER TABLE admission_exam_settings MODIFY exam_purpose ENUM('diagnostic_only','admission_requirement') NOT NULL DEFAULT 'diagnostic_only'");

        Schema::table('admission_exam_settings', function (Blueprint $table) {
            $table->dropColumn('scholarship_bands');
        });
    }
};
