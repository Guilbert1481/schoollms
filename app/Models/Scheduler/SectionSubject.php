<?php

namespace App\Models\Scheduler;

use Illuminate\Database\Eloquent\Model;

class SectionSubject extends Model
{
    protected $table = 'section_subjects';

    protected $fillable = [
        'section_id', 'subject_id', 'units', 'hours_per_week',
    ];

    protected $casts = [
        'units'          => 'decimal:2',
        'hours_per_week' => 'decimal:2',
    ];
}
