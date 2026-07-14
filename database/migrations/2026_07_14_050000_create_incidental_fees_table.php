<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-off / incidental fees (a school-play ticket, a fair t-shirt, a field-trip
 * levy) — charges that are NOT part of the recurring tuition assessment.
 *
 * Kept in its own table (NOT finance_fee_setups) on purpose: a row here must
 * never be picked up by InvoiceService::applicableFees at enrollment billing,
 * or every newly-enrolled student would be silently charged the incidental.
 * Instead, adding a row fans out a one-time invoice to the students it covers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidental_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();

            $table->string('name');                 // the "Item" — e.g. "School Play Ticket"
            $table->text('description')->nullable();

            // Scope: who the incidental covers. Any of these left NULL widens the
            // scope on that axis (NULL education_node = all levels, etc.).
            $table->unsignedBigInteger('education_node_id')->nullable()->index();
            $table->unsignedBigInteger('program_id')->nullable();       // non-basic ed only
            $table->unsignedInteger('year_level')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();

            $table->decimal('amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('charged_at')->nullable();   // when the fan-out last ran
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidental_fees');
    }
};
