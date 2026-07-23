<?php

namespace App\Models\Games;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One buzz: a single player's attempt at a single question. The unique
 * (question, player) DB index is the anti-spam / arbitration guard — one shot
 * per player per question.
 */
class GameSessionAnswer extends Model
{
    protected $fillable = [
        'game_session_id', 'game_session_question_id', 'game_session_player_id',
        'choice_index', 'answer_text', 'is_correct',
    ];

    protected $casts = [
        'choice_index' => 'integer',
        'is_correct' => 'boolean',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(GameSessionPlayer::class, 'game_session_player_id');
    }
}
