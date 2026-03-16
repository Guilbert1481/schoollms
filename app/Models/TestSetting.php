<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSetting extends Model
{
    use HasFactory;

    protected $table = 'test_settings';

    protected $fillable = [
        'test_id',
        'timer_minutes',
        'attempts_allowed',
        'passing_score',
        'show_results',
        'show_correct_answers',
        'shuffle_questions',
        'shuffle_mcq_choices',
        'start_at',
        'end_at',
        'mode',
        'term',
        'assessment_type',
    ];

    protected $casts = [
        'shuffle_questions' => 'boolean',
        'shuffle_mcq_choices' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'term' => 'string',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }
}