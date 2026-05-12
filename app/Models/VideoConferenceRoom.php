<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoConferenceRoom extends Model
{
    protected $fillable = [
        'school_id',
        'title',
        'description',
        'context_name',
        'group_id',
        'permission_id',
        'created_by',
        'owner_user_id',
        'status',
        'starts_at',
        'ended_at',
        'auto_end_minutes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ended_at' => 'datetime',
        'auto_end_minutes' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function permission()
    {
        return $this->belongsTo(VideoConferencePermission::class, 'permission_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function sessions()
    {
        return $this->hasMany(VideoConferenceSession::class, 'room_id');
    }

    public function activeSession()
    {
        return $this->hasOne(VideoConferenceSession::class, 'room_id')
            ->where('status', 'live');
    }

    public function latestSession()
    {
        return $this->hasOne(VideoConferenceSession::class, 'room_id')->latestOfMany('started_at');
    }
}
