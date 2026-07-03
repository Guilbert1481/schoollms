<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

/**
 * One append-only row in a student's individual ledger.
 *
 * Positive running balance (balance_after) = the student owes the school.
 *   - charges increase the balance through `debit`
 *   - payments / discounts decrease it through `credit`
 */
class LedgerEntry extends Model
{
    use BelongsToSchool;

    public const TYPE_CHARGE     = 'charge';
    public const TYPE_PAYMENT    = 'payment';
    public const TYPE_DISCOUNT   = 'discount';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_REFUND     = 'refund';

    public const TYPES = [
        self::TYPE_CHARGE     => 'Charge',
        self::TYPE_PAYMENT    => 'Payment',
        self::TYPE_DISCOUNT   => 'Discount',
        self::TYPE_ADJUSTMENT => 'Adjustment',
        self::TYPE_REFUND     => 'Refund',
    ];

    protected $fillable = [
        'school_id',
        'student_id',
        'student_enrollment_id',
        'invoice_id',
        'payment_id',
        'academic_year_id',
        'term_id',
        'entry_date',
        'type',
        'description',
        'reference',
        'debit',
        'credit',
        'balance_after',
        'created_by',
    ];

    protected $casts = [
        'entry_date'    => 'date',
        'debit'         => 'decimal:2',
        'credit'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }
}
