<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Letterhead branding images used by printable documents (e.g. the
     * enrolment application preview/PDF): a full-width header band, a
     * full-width footer band, and a whole-page (A4) background. Each is
     * scaled to the paper proportionally when rendered.
     */
    public function up(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->string('school_header')->nullable()->after('school_hero');
            $table->string('school_footer')->nullable()->after('school_header');
            $table->string('school_background')->nullable()->after('school_footer');
        });
    }

    public function down(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->dropColumn(['school_header', 'school_footer', 'school_background']);
        });
    }
};
