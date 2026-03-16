<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class AnnouncementAssignment extends Model
{
    use BelongsToSchool;
    
    protected $fillable = [
        'announcement_id',
        'assignable_type',
        'assignable_id',
        'school_id',
    ];
}

