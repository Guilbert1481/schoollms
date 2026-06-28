<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentHealthRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'has_medical_condition',
        'medical_conditions',
        'other_medical_condition',
        'allergies',
        'takes_maintenance_medication',
        'medication_name',
        'medication_dosage',
        'medication_schedule',
        'blood_type',
        'emergency_medical_instructions',
        'is_pwd',
        'pwd_type',
        'pwd_id_number',
        'doctor_name',
        'doctor_contact',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_mobile',
        'emergency_contact_alt',
    ];

    protected $casts = [
        'has_medical_condition'        => 'boolean',
        'takes_maintenance_medication' => 'boolean',
        'is_pwd'                       => 'boolean',
        'medical_conditions'           => 'array',
    ];

    /**
     * The columns considered sensitive — hidden from default array/JSON output.
     */
    protected $hidden = [
        'medical_conditions',
        'other_medical_condition',
        'allergies',
        'medication_name',
        'medication_dosage',
        'medication_schedule',
        'emergency_medical_instructions',
        'pwd_id_number',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
