<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the orphan FK on enrollment_drafts that still points to academic_terms.
        // The application no longer uses academic_terms; terms live in the `terms` table.
        if (Schema::hasTable('enrollment_drafts') && Schema::hasColumn('enrollment_drafts', 'academic_term_id')) {
            Schema::table('enrollment_drafts', function ($table) {
                try { $table->dropForeign(['academic_term_id']); } catch (\Throwable $e) {}
            });
        }

        Schema::dropIfExists('academic_terms');
    }

    public function down(): void
    {
        // Legacy table; not recreated.
    }
};
