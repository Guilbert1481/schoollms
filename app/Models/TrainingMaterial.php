<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingMaterial extends Model
{
    protected $fillable = [
        'training_course_id',
        'title',
        'file_path',
        'description'
    ];

    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'training_course_id');
    }
}