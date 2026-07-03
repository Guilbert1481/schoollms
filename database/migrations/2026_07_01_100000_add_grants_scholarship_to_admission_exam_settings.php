<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scholarship is now an INDEPENDENT toggle rather than an exclusive exam
     * purpose: an exam grants scholarships on top of being either a diagnostic
     * or an admission-requirement exam (at most 2 stances). Existing
     * "scholarship_grants" rows are migrated to diagnostic + grants_scholarship.
     */
    public function up(): void
    {
        Schema::table('admission_exam_settings', function (Blueprint $table) {
            $table->boolean('grants_scholarship')->default(false)->after('exam_purpose');
        });

        DB::table('admission_exam_settings')
            ->where('exam_purpose', 'scholarship_grants')
            ->update(['grants_scholarship' => true, 'exam_purpose' => 'diagnostic_only']);
    }

    public function down(): void
    {
        DB::table('admission_exam_settings')
            ->where('grants_scholarship', true)
            ->update(['exam_purpose' => 'scholarship_grants']);

        Schema::table('admission_exam_settings', function (Blueprint $table) {
            $table->dropColumn('grants_scholarship');
        });
    }
};
