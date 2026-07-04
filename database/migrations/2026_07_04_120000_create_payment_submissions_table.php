<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online-checkout proof-of-payment submissions.
 *
 * These are PENDING declarations by a student/parent that they have paid an
 * invoice (with an uploaded proof image/PDF). They deliberately live OUTSIDE
 * the `payments` table so that nothing here touches the ledger, the invoice
 * balance, the enrolment payment gate, or revenue KPIs — all of which sum
 * `payments.amount` and must only ever see money that finance has verified.
 *
 * On verify, finance turns a submission into a real Payment (via PaymentService)
 * which is what actually credits the ledger; the submission then records the
 * resulting payment_id for the audit trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            // Money the payer is settling against the invoice (what will be
            // credited on verify) and the platform convenience fee they were
            // charged on top at checkout time. Kept separate so the ledger only
            // ever receives the invoice portion.
            $table->decimal('amount', 12, 2);
            $table->decimal('system_fee', 12, 2)->default(0);

            $table->string('payment_method');
            $table->string('reference_number')->nullable();
            $table->string('proof_path');

            // pending -> verified | rejected. Never anything else.
            $table->string('status')->default('pending')->index();

            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();

            // The real Payment created when finance verifies this submission.
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_submissions');
    }
};
