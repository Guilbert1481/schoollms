<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Auditable, HasFactory, Notifiable;

    /**
     * Audit privilege changes only (Phase 3 / H2): role, tenant, and
     * active-status moves. Ordinary profile edits stay out of the audit log.
     */
    protected array $auditOnly = ['role', 'school_id', 'status'];

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
            // Parent-portal provisioning fields. Deliberately NOT fillable:
            // they are set explicitly by the provisioning service, never from
            // request input.
            'password_change_required' => 'boolean',
            'credentials_sent_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE NORMALIZATION
    |--------------------------------------------------------------------------
    | Admins entering free-form labels in user management ("Course Architect",
    | "Program Head", "course-architect") are normalized to canonical
    | snake_case at write time so middleware checks match consistently.
    */

    public function setRoleAttribute($value): void
    {
        $this->attributes['role'] = self::normalizeRole($value);
    }

    public static function normalizeRole($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        // Collapse any whitespace or hyphens into a single underscore.
        $value = preg_replace('/[\s\-]+/', '_', $value);

        return $value === '' ? null : $value;
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
        return in_array($this->role, ['admission_manager'], true);
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Parent-portal account (auto-provisioned from a guardian email).
     * Parents are cross-school: school_id stays NULL and every access
     * decision goes through the parent_student links instead.
     */
    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    public function isDean(): bool
    {
        return $this->role === 'dean';
    }

    public function isPrincipal(): bool
    {
        return $this->role === 'principal';
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
    | TWO-FACTOR (Google Authenticator) HELPERS
    |--------------------------------------------------------------------------
    */

    /** True once the user has enrolled an authenticator (2FA is ON). */
    public function twoFactorEnabled(): bool
    {
        return ! empty($this->google2fa_secret);
    }

    /**
     * True when 2FA cannot be turned off for this account: enforcement is on
     * AND the role is in the mandatory list (M2). Such users may enable but
     * never disable their second factor from the profile toggle.
     */
    public function twoFactorMandatory(): bool
    {
        return (bool) config('security.enforce_2fa', true)
            && in_array($this->role, (array) config('security.two_factor_mandatory_roles', []), true);
    }

    /*
    |--------------------------------------------------------------------------
    | ENROLLMENT RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Enrolments where this user is the student. NOTE: student_enrollments.student_id
     * references students.id (not users.id), so this hasMany routes through the
     * student profile via hasManyThrough.
     */
    public function enrollments()
    {
        return $this->hasManyThrough(
            StudentEnrollment::class,
            Student::class,
            'user_id',   // FK on students table
            'student_id', // FK on student_enrollments table
            'id',        // PK on users table
            'id'         // PK on students table
        );
    }

    public function approvedEnrollments()
    {
        return $this->hasMany(StudentEnrollment::class, 'approved_by');
    }

    public function profile()
    {
        return $this->hasOne(\App\Models\Profile::class);
    }

    /**
     * The student profile row for this user (created on enrolment Step 1).
     * Used throughout the public enrolment wizard.
     */
    public function student()
    {
        return $this->hasOne(\App\Models\Student::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | PARENT PORTAL RELATIONSHIPS (users with role = 'parent')
    |--------------------------------------------------------------------------
    */

    /** Students this parent-portal user is linked to (their children). */
    public function children()
    {
        return $this->belongsToMany(\App\Models\Student::class, 'parent_student', 'parent_user_id', 'student_id')
            ->withPivot(['relationship', 'is_primary', 'access_status'])
            ->withTimestamps();
    }

    /** The raw access-grant rows (for the registrar panel / auditing). */
    public function parentLinks()
    {
        return $this->hasMany(\App\Models\ParentStudentLink::class, 'parent_user_id');
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
            'admin' => 'bg-indigo-100 text-indigo-600',
            'admission_manager' => 'bg-emerald-100 text-emerald-600',
            'finance_manager' => 'bg-violet-100 text-violet-600',
            'registrar' => 'bg-sky-100 text-sky-600',
            'academics' => 'bg-purple-100 text-purple-600',
            'teacher' => 'bg-amber-100 text-amber-600',
            'student' => 'bg-slate-100 text-slate-600',
            'program_head' => 'bg-blue-100 text-blue-600',
            'dean' => 'bg-rose-100 text-rose-600', // Add this line
            'principal' => 'bg-orange-100 text-orange-600',
            'guidance_counselor' => 'bg-teal-100 text-teal-600',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function getFullNameAttribute()
    {
        $middle = $this->middle_name
            ? ' '.strtoupper(substr($this->middle_name, 0, 1)).'.'
            : '';

        return trim($this->first_name.$middle.' '.$this->last_name);
    }

    protected $appends = ['full_name'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function accountAccess()
    {
        return $this->hasMany(\App\Models\AccountAccess::class, 'user_id');
    }

    public function colleges()
    {
        return $this->belongsToMany(College::class, 'user_colleges');
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'user_programs');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function handledOffice()
    {
        return $this->hasOne(Office::class, 'office_head_id');
    }

    public function handledCollege()
    {
        return $this->hasOne(College::class, 'dean_id');
    }

    public function handledProgram()
    {
        return $this->hasOne(Program::class, 'program_head_id');
    }

    public function hasTraining()
    {
        if (! $this->profile) {
            return false;
        }

        return \App\Models\TrainingEnrollment::where('trainee_id', $this->profile->id)->exists();
    }
}
