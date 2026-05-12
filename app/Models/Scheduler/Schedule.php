<?php

namespace App\Models\Scheduler;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $table = 'schedules';

    protected $fillable = [
        'school_id', 'term_id', 'name', 'version',
        'score', 'is_active', 'meta', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'score'     => 'decimal:2',
        'meta'      => 'array',
    ];

    public function sessions()
    {
        return $this->hasMany(ScheduleSession::class, 'schedule_id');
    }
}
