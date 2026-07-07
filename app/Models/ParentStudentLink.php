<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One parent-portal access grant: parent user ↔ student. Deliberately
 * separate from `guardians` (contact rows the enrollment wizard deletes and
 * recreates on every save). See the parent_student migration for rationale.
 */
class ParentStudentLink extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';

    protected $table = 'parent_student';

    protected $fillable = [
        'parent_user_id',
        'student_id',
        'relationship',
        'is_primary',
        'access_status',
        'created_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function parentUser()
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function isActive(): bool
    {
        return $this->access_status === self::STATUS_ACTIVE;
    }
}
