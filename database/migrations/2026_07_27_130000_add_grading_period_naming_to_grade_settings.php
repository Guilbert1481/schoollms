<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Principal → Settings → Grades → Division: per-school naming of the basic-ed
 * grading periods. The period stays an ordinal (1..N); only its display is
 * configurable — the collective noun (period_label, e.g. "Quarter"/"Term") and
 * the per-period names (period_names, e.g. ["1st Quarter", …]). Reads fall back
 * to sensible defaults when these are null, so existing rows keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_settings', function (Blueprint $table) {
            $table->string('period_label', 40)->default('Quarter')->after('show_student_form137');
            $table->json('period_names')->nullable()->after('period_label');
        });
    }

    public function down(): void
    {
        Schema::table('grade_settings', function (Blueprint $table) {
            $table->dropColumn(['period_label', 'period_names']);
        });
    }
};
