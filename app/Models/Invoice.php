<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class Invoice extends Model
{
    use BelongsToSchool;

    public const STATUS_PAID    = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_UNPAID  = 'unpaid';

    protected $fillable = [
        'invoice_number',
        'school_id',
        'student_id',
        'student_enrollment_id',
        'academic_year_id',
        'term_id',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'balance',
        'status',
        'due_date',
        'issue_date',
        'billing_date',
        'issued_by',
        'notes',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'balance'         => 'decimal:2',
        'due_date'        => 'date',
        'issue_date'      => 'date',
        'billing_date'    => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Recompute paid_amount / balance / status from linked payments.
     * Returns the model (unsaved) so callers can persist within a transaction.
     */
    public function recomputeTotals(): self
    {
        $paid  = round((float) $this->payments()->sum('amount'), 2);
        $total = round((float) $this->total_amount, 2);

        // Balance may go negative when a student overpays — that is a credit,
        // not an error, so it must surface rather than clamp to zero.
        $this->paid_amount = $paid;
        $this->balance     = round($total - $paid, 2);

        $shortfall = round($total - $paid, 2);

        if ($paid <= 0.005) {
            $this->status = self::STATUS_UNPAID;
        } elseif ($shortfall > 0.005) {
            $this->status = self::STATUS_PARTIAL;
        } else {
            $this->status = self::STATUS_PAID;
        }

        return $this;
    }

    public function isOverdue(): bool
    {
        return $this->balance > 0
            && $this->due_date
            && $this->due_date->isPast();
    }

    /**
     * Bills whose billing date has arrived (on or before $asOf, default today).
     * This is what the Billing page shows: the current + past bills — including
     * everything still unpaid — while installment invoices dated to a future
     * month stay hidden until their billing date. Legacy rows without a
     * billing_date are treated as already billed so nothing pre-existing hides.
     */
    public function scopeBilled($query, $asOf = null)
    {
        $date = ($asOf ? \Illuminate\Support\Carbon::parse($asOf) : \Illuminate\Support\Carbon::now())->toDateString();

        return $query->where(function ($q) use ($date) {
            $q->whereNull('billing_date')->orWhereDate('billing_date', '<=', $date);
        });
    }
}
