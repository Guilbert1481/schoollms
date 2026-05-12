<?php

namespace App\Models\Scheduler;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms';

    protected $fillable = [
        'school_id', 'name', 'code', 'capacity', 'type', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity'  => 'integer',
    ];
}
