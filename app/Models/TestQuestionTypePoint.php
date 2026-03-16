<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestQuestionTypePoint extends Model
{
    protected $fillable = [
        'test_id',
        'question_type',
        'points'
    ];

    // If you want created_at and updated_at columns to work (usually yes)
    public $timestamps = true;
}