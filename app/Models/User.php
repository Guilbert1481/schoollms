<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'school_id',
        'role',
        'phone',
        'profile_photo',
        'dashboard_identity', // unified theme engine
    ];

    /*
    |--------------------------------------------------------------------------
    | Hidden Attributes
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'dashboard_identity' => 'array', // important for JSON theme
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | SCHOOL RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE HELPERS
    |--------------------------------------------------------------------------
    */

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVpAcademics(): bool
    {
        return $this->role === 'academics';
    }

    public function isAdmissionManager(): bool
    {
        return $this->role === 'admission';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isDean(): bool
    {
        return $this->role === 'dean';
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS CHECK
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return (bool) $this->status;
    }

    /*
    |--------------------------------------------------------------------------
    | ENROLLMENT RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function approvedEnrollments()
    {
        return $this->hasMany(Enrollment::class, 'approved_by');
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /*
    |--------------------------------------------------------------------------
    | CHAT THREADS RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function chatThreads()
    {
        return $this->belongsToMany(ChatThread::class)
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }

    public function deadlineCompletions()
    {
        return $this->hasMany(DeadlineUserCompletion::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function teacherProfile()
    {
        return $this->hasOne(TeacherProfile::class);
    }


    public function getRoleBadgeClassAttribute()
    {
        return match ($this->role) {
            'admin'        => 'bg-indigo-100 text-indigo-600',
            'admission'    => 'bg-emerald-100 text-emerald-600',
            'academics'    => 'bg-purple-100 text-purple-600',
            'teacher'      => 'bg-amber-100 text-amber-600',
            'student'      => 'bg-slate-100 text-slate-600',
            'program_head' => 'bg-blue-100 text-blue-600',
            'dean'         => 'bg-rose-100 text-rose-600', // Add this line
            default        => 'bg-gray-100 text-gray-600',
        };
    }

    public function getFullNameAttribute()
    {
        $middle = $this->middle_name
            ? ' ' . strtoupper(substr($this->middle_name, 0, 1)) . '.'
            : '';

        return trim($this->first_name . $middle . ' ' . $this->last_name);
    }

protected $appends = ['full_name'];





}
