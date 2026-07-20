<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * sections.status is 'draft' | 'published' | 'archived', but the registrar
     * ledger import used to create sections with the legacy value 'active'.
     * Those sections are live (students were imported into them), yet
     * SectionPublishedValidator blocks enrolment for any status other than
     * 'published' and the Sections page counts them as neither draft nor
     * published. Same rationale as the 2026_04_28 backfill: live data must not
     * be blocked from enrolment.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sections', 'status')) {
            return;
        }

        DB::table('sections')->where('status', 'active')->update(['status' => 'published']);
    }

    public function down(): void
    {
        // Irreversible by design: 'active' rows are indistinguishable from
        // ordinary published rows once normalized, and restoring the legacy
        // value would only re-break enrolment.
    }
};
