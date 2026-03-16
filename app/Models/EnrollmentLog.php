<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'old_status',
        'new_status',
        'changed_by',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

