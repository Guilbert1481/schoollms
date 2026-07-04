<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A pending online-checkout proof-of-payment declaration.
 *
 * Distinct from Payment on purpose: a submission is money the payer *claims*
 * to have sent, evidenced by an uploaded proof, and does NOT affect the ledger,
 * the invoice balance, or revenue until finance verifies it. Verifying creates
 * the real Payment (see PaymentService) and links it back via payment_id.
 *
 * @property-read \App\Models\Invoice|null $invoice
 * @property-read \App\Models\User|null $student
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\Payment|null $payment
 */
class PaymentSubmission extends Model
{
    use BelongsToSchool;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'school_id',
        'student_id',
        'invoice_id',
        'amount',
        'system_fee',
        'payment_method',
        'reference_number',
        'proof_path',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'payment_id',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'system_fee'   => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** Public URL to the uploaded proof, or null when none is stored. */
    public function proofUrl(): ?string
    {
        return $this->proof_path ? Storage::disk('public')->url($this->proof_path) : null;
    }
}
