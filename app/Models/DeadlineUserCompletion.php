<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DeadlineUserCompletion extends Model
{


    protected $fillable = [
        'deadline_id',
        'user_id',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime'
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