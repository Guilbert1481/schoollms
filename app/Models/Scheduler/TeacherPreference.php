<?php

namespace App\Models\Scheduler;

use Illuminate\Database\Eloquent\Model;

class TeacherPreference extends Model
{
    protected $table = 'teacher_preferences';

    protected $fillable = [
        'teacher_id', 'preferred_block', 'max_hours_per_day', 'max_hours_per_week',
    ];

    protected $casts = [
        'max_hours_per_day'  => 'decimal:2',
        'max_hours_per_week' => 'decimal:2',
    ];
}
