<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-school finance configuration. The finance office sets the SOA generation
 * cadence here; the scheduled `finance:generate-soas` command reads it to
 * decide when to auto-generate statements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained('schools')->cascadeOnDelete();

            $table->string('soa_frequency', 20)->default('per_term'); // monthly|quarterly|per_term|annual|on_demand
            $table->unsignedTinyInteger('soa_generation_day')->default(1); // day-of-month anchor
            $table->boolean('auto_generate_soa')->default(true);
            $table->boolean('auto_invoice_on_billing')->default(true);
            $table->unsignedSmallInteger('invoice_due_days')->default(7);
            $table->string('currency', 10)->default('PHP');
            $table->text('soa_footer_note')->nullable();
            $table->timestamp('last_soa_run_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_settings');
    }
};
