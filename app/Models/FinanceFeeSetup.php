<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceFeeSetup extends Model
{
    public const FEE_TYPES = [
        'tuition' => 'Tuition',
        'miscellaneous' => 'Miscellaneous',
        'registration' => 'Registration',
        'laboratory' => 'Laboratory',
        'other' => 'Other',
    ];

    public const BILLING_BASES = [
        'fixed' => 'Fixed Amount',
        'per_unit' => 'Per Unit',
    ];

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'term_id',
        'education_node_id',
        'program_id',
        'year_level',
        'fee_type',
        'code',
        'name',
        'billing_basis',
        'amount',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'year_level' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function educationNode()
    {
        return $this->belongsTo(EducationNode::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
