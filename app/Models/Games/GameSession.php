<?php

namespace App\Models\Games;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A live multiplayer Gamified-Quiz match (Tug-of-War first).
 *
 * School-scoped by construction (BelongsToSchool global scope + auto school_id
 * on create). The drawn question set lives in {@see GameSessionQuestion} with
 * the correct answer snapshotted server-side, so grading never trusts the client.
 */
class GameSession extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'host_id', 'slug', 'join_code', 'mode',
        'question_type', 'difficulty', 'question_count', 'round_seconds',
        'academic_level_id', 'subject_id', 'topic_id', 'lesson_id', 'competency_id',
        'status', 'current_sequence', 'rope_position',
        'team_a_score', 'team_b_score', 'team_a_correct', 'team_b_correct',
        'team_a_label', 'team_b_label', 'question_ends_at', 'winner',
    ];

    protected $casts = [
        'question_count' => 'integer',
        'round_seconds' => 'integer',
        'current_sequence' => 'integer',
        'rope_position' => 'integer',
        'team_a_score' => 'integer',
        'team_b_score' => 'integer',
        'team_a_correct' => 'integer',
        'team_b_correct' => 'integer',
        'question_ends_at' => 'datetime',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(GameSessionPlayer::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(GameSessionQuestion::class)->orderBy('sequence');
    }

    /** The question the match is currently on (null in lobby / when ended). */
    public function currentQuestion(): ?GameSessionQuestion
    {
        if ($this->current_sequence < 1) {
            return null;
        }

        return $this->questions()->where('sequence', $this->current_sequence)->first();
    }
}
