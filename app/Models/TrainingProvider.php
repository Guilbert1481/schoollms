<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingProvider extends Model
{
    protected $fillable = [
        'name',
        'description',
        'logo',
    ];

    public function courses()
    {
        return $this->hasMany(TrainingCourse::class);
    }
}