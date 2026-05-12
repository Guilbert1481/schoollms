<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const TYPES = [
        'tuition',
        'registration',
        'miscellaneous',
        'training',
    ];

    protected $fillable = [
        'school_id',
        'student_id',
        'training_enrollment_id',
        'amount',
        'reference_number',
        'payment_method',
        'payment_type',
        'paid_at',
    ];

    protected $casts = [
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
}
