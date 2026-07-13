<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A credited / transferred subject legitimately has no numeric grade, so the
 * transcript-edit audit log must allow a null new_grade (it was NOT NULL,
 * which broke crediting a subject on Form 137 and the TOR alike).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transcript_edit_requests', function (Blueprint $table) {
            $table->decimal('new_grade', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transcript_edit_requests', function (Blueprint $table) {
            $table->decimal('new_grade', 5, 2)->nullable(false)->change();
        });
    }
};
