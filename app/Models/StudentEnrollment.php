<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

/**
 * Canonical academic enrolment record.
 *
 * One row = one student × one term × one program enrolment lifecycle:
 *   draft → submitted → assessed → billed → partially_paid → enrolled
 *                                                       → dropped/cancelled/completed
 */
class StudentEnrollment extends Model
{
    use HasFactory, BelongsToSchool;

    protected $table = 'student_enrollments';

    protected $fillable = [
        'school_id',
        'student_id',
        'academic_year_id',
        'term_id',
        'program_id',
        'payment_plan_id',
        'payment_option',
        'payment_frequency',
        'scholarship_label',
        'scholarship_amount',
        'scholarship_percent',
        'scholarship_apply_to',
        'agreed_to_penalty',
        'certified_by',
        'acknowledged_accuracy',
        'data_privacy_consent',
        'certified_at',
        'modality_id',
        'education_node_id',
        'year_level',
        'student_type',
        'campus_id',
        'education_level',
        'program_type',
        'enrollee_type',
        'enrollment_setting_id',
        'section_id',
        'total_units',
        'status',
        'billing_cleared_as',
        'payment_due_at',
        'approved_by',
        'approved_at',
        'approval_level',
        'remarks',
    ];

    protected $casts = [
        'total_units'           => 'decimal:2',
        'scholarship_amount'    => 'decimal:2',
        'scholarship_percent'   => 'decimal:2',
        'approved_at'           => 'datetime',
        'payment_due_at'        => 'datetime',
        'agreed_to_penalty'     => 'boolean',
        'acknowledged_accuracy' => 'boolean',
        'data_privacy_consent'  => 'boolean',
        'certified_at'          => 'datetime',
    ];

    /* -----------------------------------------------------------------
     | Lifecycle status constants
     |----------------------------------------------------------------*/
    public const STATUS_DRAFT          = 'draft';
    public const STATUS_SUBMITTED      = 'submitted';
    public const STATUS_EXAM_PASSED    = 'exam_passed';
    public const STATUS_EXAM_FAILED    = 'exam_failed';
    public const STATUS_ASSESSED       = 'assessed';
    public const STATUS_PROVISIONAL    = 'provisional';
    public const STATUS_REJECTED       = 'rejected';
    public const STATUS_SENT_BILLING   = 'sent_billing';
    public const STATUS_BILLED         = 'billed';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_ENROLLED       = 'enrolled';
    public const STATUS_PROVISIONALLY_ENROLLED = 'provisionally_enrolled';
    public const STATUS_DROPPED        = 'dropped';
    public const STATUS_CANCELLED      = 'cancelled';
    public const STATUS_COMPLETED      = 'completed';

    /* -----------------------------------------------------------------
     | Relationships
     |----------------------------------------------------------------*/
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }

    public function educationNode()
    {
        return $this->belongsTo(EducationNode::class, 'education_node_id');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function setting()
    {
        return $this->belongsTo(EnrollmentSetting::class, 'enrollment_setting_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function subjects()
    {
        return $this->hasMany(StudentEnrollmentSubject::class, 'student_enrollment_id');
    }

    public function logs()
    {
        return $this->hasMany(EnrollmentLog::class, 'enrollment_id');
    }

    public function documents()
    {
        return $this->hasMany(EnrollmentDocument::class, 'enrollment_id');
    }

    /* -----------------------------------------------------------------
     | Lifecycle hooks
     |----------------------------------------------------------------*/
    protected static function booted(): void
    {
        // When a student's enrolment is finalised (officially "enrolled"),
        // dismiss the "Enrollment is now open" notification from their bell so
        // it stops nagging once they've completed the process.
        static::saved(function (self $enrollment) {
            if ($enrollment->status !== self::STATUS_ENROLLED) {
                return;
            }
            if (! $enrollment->isDirty('status') && ! $enrollment->wasRecentlyCreated) {
                return;
            }

            $student = $enrollment->student;
            $userId  = $student?->user_id;
            if (! $userId || ! $enrollment->enrollment_setting_id) {
                return;
            }

            \DB::table('notifications')
                ->where('notifiable_type', \App\Models\User::class)
                ->where('notifiable_id', $userId)
                ->where('type', \App\Notifications\EnrollmentOpenNotification::class)
                ->whereNull('read_at')
                ->where('data', 'like', '%"enrollment_setting_id":'.(int) $enrollment->enrollment_setting_id.'%')
                ->update(['read_at' => now(), 'updated_at' => now()]);
        });
    }
}
