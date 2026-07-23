<?php

namespace App\Models\Games;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One drawn question in a {@see GameSession}, in play order. The correct answer
 * (correct_index / answer_text) is snapshotted here and NEVER serialized to the
 * client until the reveal — server-authoritative grading.
 */
class GameSessionQuestion extends Model
{
    protected $fillable = [
        'game_session_id', 'question_id', 'sequence', 'question_text',
        'choices', 'correct_index', 'answer_text', 'explanation',
        'answered_by_player_id', 'answered_team', 'was_correct', 'answered_at',
    ];

    protected $casts = [
        'choices' => 'array',
        'sequence' => 'integer',
        'correct_index' => 'integer',
        'was_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    /** Whether the first correct buzz has already closed this question. */
    public function isClosed(): bool
    {
        return $this->answered_at !== null;
    }

    /**
     * The client-safe shape: everything EXCEPT the correct answer, unless the
     * question is already closed (then the reveal is allowed).
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(bool $reveal = false): array
    {
        $reveal = $reveal || $this->isClosed();

        return [
            'sequence' => $this->sequence,
            'question' => $this->question_text,
            'choices' => $this->choices,
            'explanation' => $reveal ? $this->explanation : null,
            'correct_index' => $reveal ? $this->correct_index : null,
            'answer_text' => $reveal ? $this->answer_text : null,
            'answered_team' => $this->answered_team,
            'was_correct' => $this->was_correct,
            'closed' => $this->isClosed(),
        ];
    }
}
