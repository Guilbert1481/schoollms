<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class Student extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'home_school_id',
        'user_id',
        'student_number',
        'first_name',
        'middle_name',
        'last_name',
        'preferred_name', // Added to match your UI
        'date_of_birth',
        'gender',
        'sexual_orientation', // Added demographic field
        'nationality',
        'civil_status',
        'religion',
        'government_id_type',
        'government_id_number',
        'photo_path', // For Profile Photo
        'photo_id',   // For ID Document
        'email',
        'phone',
        'mobile_number',
        'landline_number',
        'unit_number',
        'building_name',
        'street',
        'subdivision',
        'barangay',
        'city_municipality',
        'province',
        'region',
        'country',
        'country_code',
        'address_line_1',
        'address_line_2',
        'zip_code',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
    
    /**
     * Helper to get full name in "Linear" style dashboards
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_student')
            ->withPivot(['enrollment_id', 'status'])
            ->withTimestamps();
    }

    public function guardians()
    {
        return $this->hasMany(Guardian::class);
    }

    public function academicBackgrounds()
    {
        return $this->hasMany(StudentAcademicBackground::class);
    }

    public function healthRecord()
    {
        return $this->hasOne(StudentHealthRecord::class);
    }

    public function studentEnrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

}