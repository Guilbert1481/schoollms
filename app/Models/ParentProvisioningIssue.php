<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A problem the parent-portal auto-provisioner could not resolve on its own
 * (no guardian email / email collision / credential send failure), surfaced
 * on the registrar's Parent Portal panel.
 */
class ParentProvisioningIssue extends Model
{
    public const ISSUE_NO_EMAIL = 'no_email';
    public const ISSUE_EMAIL_COLLISION = 'email_collision';
    public const ISSUE_SEND_FAILED = 'send_failed';

    public const LABELS = [
        self::ISSUE_NO_EMAIL => 'No guardian email on file',
        self::ISSUE_EMAIL_COLLISION => 'Email belongs to a non-parent account',
        self::ISSUE_SEND_FAILED => 'Credential email failed to send',
    ];

    protected $fillable = [
        'school_id',
        'student_id',
        'student_enrollment_id',
        'issue',
        'email',
        'details',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->issue] ?? ucfirst(str_replace('_', ' ', (string) $this->issue));
    }
}
