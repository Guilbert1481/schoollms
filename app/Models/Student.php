<?php

namespace App\Models;

use App\Casts\EncryptedTolerant;
use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'home_school_id',
        'user_id',
        'show_grades',
        'show_form137',
        'student_number',
        'lrn',
        'first_name',
        'middle_name',
        'last_name',
        'preferred_name', // Added to match your UI
        'date_of_birth',
        'place_of_birth',
        'blood_type',
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

    /**
     * Encryption at rest for crown-jewel PII (Roadmap D2). A minor's
     * government-ID NUMBER is stored ciphertext via Laravel's `encrypted`
     * cast — a DB-level leak yields no usable IDs. Safe to encrypt because
     * the column is only ever written and displayed, never queried by value.
     * NOTE: encrypted with APP_KEY, so APP_KEY rotation must re-encrypt these
     * (P1 key-rotation procedure); existing rows are migrated by
     * `students:encrypt-government-ids`. LRN is deliberately NOT encrypted —
     * it is a functional identifier (ID-card barcode source, lookups).
     *
     * Uses EncryptedTolerant (not the vanilla `encrypted` cast) so a legacy
     * plaintext row — one not yet run through the backfill — reads back as
     * plaintext instead of throwing DecryptException. This makes the D2 deploy
     * order-independent: the backfill hardens data at rest but is no longer a
     * prerequisite for the app to serve. See {@see EncryptedTolerant}.
     */
    protected $casts = [
        'government_id_number' => EncryptedTolerant::class,
        // Registrar-owned grade-view visibility (default off; see the
        // add_grade_visibility_to_students migration).
        'show_grades' => 'boolean',
        'show_form137' => 'boolean',
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

    /** Parent-portal users granted access to this student (co-parents supported). */
    public function parentUsers()
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_user_id')
            ->withPivot(['relationship', 'is_primary', 'access_status'])
            ->withTimestamps();
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

    public function drafts()
    {
        return $this->hasMany(EnrollmentDraft::class);
    }
}
