<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSource extends Model
{
    use HasFactory;

    protected $table = 'test_sources';

    protected $fillable = [
        'test_id',
        'source_type',
        'source_id',
        'mcq_count',
        'tf_count',
        'mtf_count',
        'identification_count',
        'matching_count',
        'fib_count',
        'enumeration_count',
        'essay_count',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }
}