<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class StatementOfAccount extends Model
{
    use Auditable;

    protected $table = 'statement_of_accounts';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'soa_number',
        'school_id',
        'student_id',
        'student_enrollment_id',
        'academic_year_id',
        'term_id',
        'frequency',
        'period_start',
        'period_end',
        'period_label',
        'opening_balance',
        'total_charges',
        'total_credits',
        'closing_balance',
        'due_date',
        'status',
        'line_items',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_charges' => 'decimal:2',
        'total_credits' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'line_items' => 'array',
        'generated_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }
}
