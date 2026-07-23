<?php

namespace App\Models\Games;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A participant in a {@see GameSession}. Tenant scope is inherited through the
 * parent session (always loaded/authorized via the school-scoped session), so
 * this child model is not itself globally scoped.
 */
class GameSessionPlayer extends Model
{
    protected $fillable = [
        'game_session_id', 'user_id', 'student_id', 'display_name',
        'team', 'role', 'status', 'is_cpu', 'score', 'correct_count', 'avatar_seed',
    ];

    protected $casts = [
        'is_cpu' => 'boolean',
        'score' => 'integer',
        'correct_count' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }
}
