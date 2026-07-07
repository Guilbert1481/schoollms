<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-school registrar preferences (one row per school, firstOrCreate'd on
 * demand — same singleton pattern as finance_settings / student_id_settings).
 * First occupant: the parent-portal auto-provisioning kill-switch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrar_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->unique()->constrained('schools')->cascadeOnDelete();

            // Auto-create parent portal accounts when the registrar clears an
            // enrollment (assessed / provisional). ON by default; the
            // registrar can disable it if the rules aren't being followed.
            $table->boolean('parent_portal_auto_provision')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrar_settings');
    }
};
