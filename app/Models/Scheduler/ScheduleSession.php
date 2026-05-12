<?php

namespace App\Models\Scheduler;

use Illuminate\Database\Eloquent\Model;

class ScheduleSession extends Model
{
    protected $table = 'schedule_sessions';

    protected $fillable = [
        'schedule_id', 'section_id', 'subject_id', 'teacher_id', 'room_id',
        'day_of_week', 'start_time', 'end_time',
        'status', 'conflict_reasons',
    ];

    protected $casts = [
        'conflict_reasons' => 'array',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
