<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeadlineAssignment extends Model
{
    protected $fillable = [
        'school_id',    
        'deadline_id',
        'user_id',
        'assignable_type',
        'assignable_id',
        'assigned_by',
        'assigned_at',
        'visible',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'visible' => 'boolean',
    ];

    public function deadline()
    {
        return $this->belongsTo(Deadline::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}