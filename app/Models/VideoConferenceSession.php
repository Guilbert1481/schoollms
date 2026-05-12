<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoConferenceSession extends Model
{
    protected $fillable = [
        'school_id',
        'room_id',
        'started_by',
        'host_user_id',
        'reopened_from_session_id',
        'status',
        'started_at',
        'auto_end_at',
        'ended_at',
        'ended_by',
        'ended_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'auto_end_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function room()
    {
        return $this->belongsTo(VideoConferenceRoom::class, 'room_id');
    }

    public function starter()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function endedBy()
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function reopenedFrom()
    {
        return $this->belongsTo(self::class, 'reopened_from_session_id');
    }

    public static function syncExpiredForSchool(int $schoolId): void
    {
        $expiredSessions = static::query()
            ->with('room')
            ->where('school_id', $schoolId)
            ->where('status', 'live')
            ->whereNotNull('auto_end_at')
            ->where('auto_end_at', '<=', now())
            ->get();

        foreach ($expiredSessions as $session) {
            $session->status = 'ended';
            $session->ended_at = $session->auto_end_at ?? now();
            $session->ended_reason = 'auto_end';
            $session->save();

            if ($session->room) {
                $session->room->status = 'ended';
                $session->room->ended_at = $session->ended_at;
                $session->room->save();
            }
        }
    }
}
