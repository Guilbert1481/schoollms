<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentSubmission;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Online checkout for a single invoice: shows the amount due + platform system
 * fee, collects a payment method and a MANDATORY proof-of-payment upload, and
 * records a PENDING PaymentSubmission. Nothing here touches the ledger — finance
 * verifies the submission later (Phase 3), which is what creates the real
 * Payment and posts the credit.
 *
 * Reachable by the invoice's own student (their invoice only) and by finance
 * staff of the same school (who may submit on behalf of a walk-in payer).
 */
class CheckoutController extends Controller
{
    /** Payment methods offered at online checkout. */
    private const METHODS = [
        'gcash' => 'GCash',
        'maya' => 'Maya',
        'bank_transfer' => 'Bank Transfer',
        'cash' => 'Cash / Over the Counter',
    ];

    public function show(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        $balance = round((float) $invoice->balance, 2);
        if ($balance <= 0) {
            return redirect($this->invoicesRoute($request))
                ->with('info', 'Invoice '.$invoice->invoice_number.' is already fully paid.');
        }

        $invoice->load(['items', 'student', 'enrollment.term', 'enrollment.program']);

        $systemFee = PlatformSetting::systemFee();

        $pending = PaymentSubmission::where('invoice_id', $invoice->id)
            ->where('status', PaymentSubmission::STATUS_PENDING)
            ->latest('id')
            ->first();

        return view('checkout.invoice', [
            'invoice' => $invoice,
            'balance' => $balance,
            'systemFee' => $systemFee,
            'total' => round($balance + $systemFee, 2),
            'methods' => self::METHODS,
            'qr' => $this->qrFor((int) $invoice->school_id),
            'pending' => $pending,
        ]);
    }

    public function submit(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        $balance = round((float) $invoice->balance, 2);
        if ($balance <= 0) {
            return redirect($this->invoicesRoute($request))
                ->with('info', 'Invoice '.$invoice->invoice_number.' is already fully paid.');
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string', 'in:'.implode(',', array_keys(self::METHODS))],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'proof.required' => 'Please attach your proof of payment.',
            'proof.mimes' => 'Proof must be an image (JPG/PNG/WebP) or a PDF.',
            'proof.max' => 'Proof must be 5 MB or smaller.',
        ]);

        // One pending submission per invoice — block accidental double-submits so
        // finance never sees (and risks double-verifying) two proofs for one bill.
        $alreadyPending = PaymentSubmission::where('invoice_id', $invoice->id)
            ->where('status', PaymentSubmission::STATUS_PENDING)
            ->exists();
        if ($alreadyPending) {
            return redirect()
                ->route('checkout.invoice.show', $invoice)
                ->with('info', 'A payment for this invoice is already awaiting verification.');
        }

        $path = $request->file('proof')->store('payment-proofs/'.(int) $invoice->school_id, 'public');

        PaymentSubmission::create([
            'school_id' => (int) $invoice->school_id,
            'student_id' => (int) $invoice->student_id,
            'invoice_id' => (int) $invoice->id,
            'amount' => $balance,
            'system_fee' => PlatformSetting::systemFee(),
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'] ?? null,
            'proof_path' => $path,
            'status' => PaymentSubmission::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('checkout.invoice.show', $invoice)
            ->with('success', 'Payment submitted. Your proof of payment is now awaiting verification by the finance office.');
    }

    /** Only the invoice's student, or finance staff of the same school, may check out. */
    private function authorizeInvoice(Request $request, Invoice $invoice): void
    {
        $user = $request->user();

        // Ownership rule lives in InvoicePolicy::pay (A1). Same-school denials
        // stay 403 (pre-Policy behavior); cross-school stays 404.
        abort_unless(
            $user->can('pay', $invoice),
            (int) $invoice->school_id === (int) $user->school_id ? 403 : 404
        );
    }

    /** Where to send the payer when there is nothing to check out. */
    private function invoicesRoute(Request $request): string
    {
        return $request->user()->isStudent()
            ? route('student.finance.invoices')
            : route('finance.invoices.index');
    }

    /** School-specific payment QR image if one has been uploaded, else null. */
    private function qrFor(int $schoolId): ?string
    {
        $path = "payment-qr/{$schoolId}.png";

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }
}
