<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student-facing grade-view visibility, managed by the Principal
 * (Settings → Grades → Student Grade). Two independent switches gate the
 * student sidebar items (and their routes): the Report Card ("Grades") and
 * the Transcript ("Form 137"). Both default OFF — hidden until a Principal
 * turns them on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_settings', function (Blueprint $table) {
            $table->boolean('show_student_grades')->default(false)->after('promotion_rule');
            $table->boolean('show_student_form137')->default(false)->after('show_student_grades');
        });
    }

    public function down(): void
    {
        Schema::table('grade_settings', function (Blueprint $table) {
            $table->dropColumn(['show_student_grades', 'show_student_form137']);
        });
    }
};
