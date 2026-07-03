<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Support\EducationLevels;

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
        'payment_plan_id',
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

    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    /**
     * Collapse matched TUITION rows to a single "most-specific node wins" charge,
     * so a broad default (e.g. a Senior High School-level tuition) and a narrower
     * override (e.g. a specific strand/grade node) never double-charge the same
     * student. The tuition whose education_node_id sits deepest in the student's
     * branch wins; a null-node tuition is the least specific. Non-tuition fees
     * are left untouched (they stack). Incoming row order is preserved.
     *
     * Used by both the Financial Assessment preview (PaymentScheduleService) and
     * the actual billing (InvoiceService) so the quote always matches the charge.
     *
     * @param  Collection<int,self>  $rows
     * @return Collection<int,self>
     */
    public static function keepMostSpecificTuition(Collection $rows, ?int $studentNodeId): Collection
    {
        $tuition = $rows->where('fee_type', 'tuition');
        if ($tuition->count() <= 1) {
            return $rows; // nothing to disambiguate
        }

        // ancestorIds() returns [self, parent, …, root]; a lower index = closer
        // to the student's own node = more specific.
        $ancestors = EducationLevels::ancestorIds((int) $studentNodeId);

        $depth = function ($nodeId) use ($ancestors) {
            if ($nodeId === null) {
                return PHP_INT_MAX; // wildcard tuition = least specific
            }
            $pos = array_search((int) $nodeId, $ancestors, true);
            return $pos === false ? PHP_INT_MAX : $pos;
        };

        // Most specific wins; ties break to the most recently created row (highest id).
        $winnerId = $tuition
            ->sort(fn ($a, $b) => [$depth($a->education_node_id), -$a->id] <=> [$depth($b->education_node_id), -$b->id])
            ->first()
            ->id;

        // Drop only the losing tuition rows; keep everything else where it was.
        return $rows->reject(fn ($r) => $r->fee_type === 'tuition' && $r->id !== $winnerId)->values();
    }
}
