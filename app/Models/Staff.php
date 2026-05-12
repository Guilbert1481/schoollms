<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'profile_id',
        'office_id',
        'employee_number',
        'position',
        'employment_type',
        'employment_status',
        'hire_date',
        'end_date'
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }
}