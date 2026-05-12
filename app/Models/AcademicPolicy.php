<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

/**
 * School-scoped academic policy.
 *
 * Resolution order for a given (school, education_level, program, term):
 *   1. school + education_level + program + term  (most specific)
 *   2. school + education_level + program
 *   3. school + education_level + term
 *   4. school + education_level
 *   5. school                                     (fallback)
 *
 * Resolved by AcademicPolicyResolver.
 */
class AcademicPolicy extends Model
{
    use HasFactory, BelongsToSchool;

    protected $table = 'academic_policies';

    protected $fillable = [
        'school_id',
        'education_level',
        'program_id',
        'term_id',
        'min_units',
        'max_units',
        'max_subjects',
        'overload_threshold_units',
        'max_section_capacity_override',
        'requires_payment_to_enrol',
        'min_payment_percent',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_units'                     => 'decimal:2',
        'max_units'                     => 'decimal:2',
        'overload_threshold_units'      => 'decimal:2',
        'min_payment_percent'           => 'decimal:2',
        'requires_payment_to_enrol'     => 'boolean',
        'is_active'                     => 'boolean',
        'effective_from'                => 'date',
        'effective_to'                  => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
