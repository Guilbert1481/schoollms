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

    /**
     * Grade/Year level, section, and academic year for finance documents
     * (invoices, SOA). Basic ed reads as "Grade N", higher ed as "Year N".
     *
     * @return array{level: string, section: ?string, academic_year: ?string}
     */
    public function financeContext(): array
    {
        $isBasic = in_array(strtolower((string) $this->education_level), ['kinder', 'elementary', 'junior_high', 'senior_high', 'basic', 'basic_ed'], true)
            || (empty($this->program_id) && ! empty($this->education_node_id));

        if ($isBasic) {
            $level = $this->education_node_id
                ? \Illuminate\Support\Facades\DB::table('education_nodes')->where('id', $this->education_node_id)->value('name')
                : null;
            $level = $level ?: ($this->year_level ? 'Grade '.(int) $this->year_level : 'Basic Ed');
        } else {
            $level = $this->year_level ? 'Year '.(int) $this->year_level : ($this->program?->name ?? '—');
        }

        return [
            'level'         => $level ?: '—',
            'section'       => $this->section_id ? \Illuminate\Support\Facades\DB::table('sections')->where('id', $this->section_id)->value('name') : null,
            'academic_year' => $this->academic_year_id ? \Illuminate\Support\Facades\DB::table('academic_years')->where('id', $this->academic_year_id)->value('name') : null,
        ];
    }

    /** Same basic-ed test financeContext() uses, exposed for feature gating. */
    public function isBasicEd(): bool
    {
        return in_array(strtolower((string) $this->education_level), ['kinder', 'elementary', 'junior_high', 'senior_high', 'basic', 'basic_ed'], true)
            || (empty($this->program_id) && ! empty($this->education_node_id));
    }

    /**
     * The official enrollment date: when the enrollment was approved, falling
     * back to row creation for legacy rows that predate approved_at.
     */
    public function officialEnrollmentDate(): ?\Illuminate\Support\Carbon
    {
        return $this->approved_at ?? $this->created_at;
    }

    /**
     * Modality change requests are accepted only for a short window at the
     * start of the term: 2 weeks from the official enrollment date.
     */
    public function modalityRequestDeadline(): ?\Illuminate\Support\Carbon
    {
        return $this->officialEnrollmentDate()?->copy()->addDays(14);
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
