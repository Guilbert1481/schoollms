<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardian extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'relationship',
        'first_name',
        'middle_name',
        'last_name',
        'occupation',
        'employer',
        'mobile_number',
        'landline_number',
        'email',
        'address',
        'is_emergency_contact',
        'is_primary',
    ];

    protected $casts = [
        'is_emergency_contact' => 'bool',
        'is_primary'           => 'bool',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
