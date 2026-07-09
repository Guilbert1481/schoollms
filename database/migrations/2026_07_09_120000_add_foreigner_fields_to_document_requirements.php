<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foreigner scoping for document requirements.
 *
 * for_foreigner: when true, this requirement's documents are shown ONLY to
 *   applicants who tick "Foreigner" on the enrollment form (in addition to
 *   their normal student-type uploads).
 * nationality: optional free-text nationality mirroring the Personal-Info
 *   nationality field. When set, the requirement applies only to foreign
 *   applicants whose entered nationality matches (case-insensitive); null on a
 *   foreigner requirement means "any foreign applicant".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requirements', function (Blueprint $table) {
            $table->boolean('for_foreigner')->default(false)->after('is_active');
            $table->string('nationality', 100)->nullable()->after('for_foreigner');
        });
    }

    public function down(): void
    {
        Schema::table('document_requirements', function (Blueprint $table) {
            $table->dropColumn(['for_foreigner', 'nationality']);
        });
    }
};
