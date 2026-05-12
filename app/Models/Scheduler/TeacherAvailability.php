<?php

namespace App\Models\Scheduler;

use Illuminate\Database\Eloquent\Model;

class TeacherAvailability extends Model
{
    protected $table = 'teacher_availabilities';

    protected $fillable = [
        'teacher_id', 'day_of_week', 'start_time', 'end_time', 'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];
}
