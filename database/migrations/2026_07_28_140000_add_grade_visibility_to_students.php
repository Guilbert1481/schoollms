<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-student grade-view visibility, owned by the Registrar (Transcript of
 * Records list). A student sees "Grades" (report card) / "Form 137" (transcript)
 * only when the matching flag is on. Default OFF — hidden until the registrar
 * grants access on request. Replaces the Principal's per-school toggle, which
 * only covered actively-enrolled basic-ed students and leaked for everyone else.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('show_grades')->default(false)->after('user_id');
            $table->boolean('show_form137')->default(false)->after('show_grades');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['show_grades', 'show_form137']);
        });
    }
};
