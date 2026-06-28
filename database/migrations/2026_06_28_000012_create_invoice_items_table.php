<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Line items that make up an invoice. Each row captures a single fee that was
 * assessed (snapshotting amount/basis at issue time so later edits to the fee
 * setup never rewrite history) and the discount, if any, applied to it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            $table->foreignId('finance_fee_setup_id')->nullable()
                ->constrained('finance_fee_setups')->nullOnDelete();
            $table->foreignId('finance_discount_type_id')->nullable()
                ->constrained('finance_discount_types')->nullOnDelete();

            $table->string('fee_type', 40)->default('other');
            $table->string('description', 191);
            $table->string('billing_basis', 20)->default('fixed'); // fixed | per_unit

            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);          // gross (quantity * unit_amount)
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);      // amount - discount_amount

            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
