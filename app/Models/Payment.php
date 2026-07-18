<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use Auditable, BelongsToSchool;

    public const TYPES = [
        'tuition',
        'registration',
        'miscellaneous',
        'training',
    ];

    protected $fillable = [
        'school_id',
        'student_id',
        'invoice_id',
        'training_enrollment_id',
        'amount',
        'reference_number',
        'payment_method',
        'payment_type',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
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
}
