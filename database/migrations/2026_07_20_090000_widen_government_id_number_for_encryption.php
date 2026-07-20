<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roadmap D2 — the `government_id_number` column becomes an encrypted cast
 * (App\Models\Student). Laravel's encrypted payload for even a short ID is a
 * few hundred bytes of base64, which overflows the old varchar(255), so the
 * column is widened to TEXT. This is a pure type change — no data is touched
 * here; existing plaintext rows are encrypted separately by the
 * `students:encrypt-government-ids` command (operator-run on live data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->text('government_id_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Down is best-effort: encrypted values will NOT fit back into
        // varchar(255) unless the operator has decrypted them first.
        Schema::table('students', function (Blueprint $table) {
            $table->string('government_id_number', 255)->nullable()->change();
        });
    }
};
