<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One append-only login-attempt row (Roadmap Phase 4). Written exclusively
 * by the LogAuthenticationActivity listener — never update or delete rows.
 */
class LoginLog extends Model
{
    public const UPDATED_AT = null;

    public const EVENT_SUCCESS = 'success';

    public const EVENT_FAILED = 'failed';

    protected $fillable = [
        'email',
        'user_id',
        'school_id',
        'event',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
