<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoConferencePermission extends Model
{
    protected $fillable = [
        'school_id',
        'teacher_id',
        'student_id',
        'group_id',
        'context_name',
        'notes',
        'allow_repeated_room_creation',
        'is_active',
        'granted_by_user_id',
    ];

    protected $casts = [
        'allow_repeated_room_creation' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    public function rooms()
    {
        return $this->hasMany(VideoConferenceRoom::class, 'permission_id');
    }
}
