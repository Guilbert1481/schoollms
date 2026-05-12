<?php

namespace App\Models\Scheduler;

use Illuminate\Database\Eloquent\Model;

class AcademicScheduler extends Model
{
    protected $table = 'academic_schedulers';

    protected $fillable = ['school_id', 'term_id', 'config', 'sessions'];

    protected $casts = [
        'config'   => 'array',
        'sessions' => 'array',
    ];
}
