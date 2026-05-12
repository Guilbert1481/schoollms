<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentType extends Model
{
    protected $table = 'enrollment_types';

    protected $fillable = [
        'name'
    ];

    public function enrollmentSettings()
    {
        return $this->hasMany(EnrollmentSetting::class);
    }
}