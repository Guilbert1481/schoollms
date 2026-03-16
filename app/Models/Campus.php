<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // Add this line
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campus extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'address',
        'status',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}

