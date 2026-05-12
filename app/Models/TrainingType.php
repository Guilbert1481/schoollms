<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingType extends Model
{
    protected $table = 'training_types';

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Each training type belongs to a school
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // One training type has many courses
    public function courses()
    {
        return $this->hasMany(TrainingCourse::class);
    }

    // One training type has one certificate template
    public function certificateTemplate()
    {
        return $this->hasOne(CertificateTemplate::class);
    }
}