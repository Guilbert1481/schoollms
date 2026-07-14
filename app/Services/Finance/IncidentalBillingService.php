<?php

namespace App\Services\Finance;

use App\Models\IncidentalFee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\StudentEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fans an incidental fee out into one-time invoices for the enrolled students
 * it covers, and posts each charge to the ledger so it surfaces on the Billing
 * page, the Statement of Account, and the individual ledger drawer.
 *
 * Idempotent: the (incidental_fee_id, student_enrollment_id) pair guards against
 * double-charging, so re-saving a fee only bills students newly in scope.
 */
class IncidentalBillingService
{
    public function __construct(
        protected LedgerService $ledger,
    ) {
    }

    /**
     * Charge the fee to every matching enrolled student. Returns the number of
     * new invoices created this run.
     */
    public function charge(IncidentalFee $fee, ?int $actorId = null): int
    {
        if (! $fee->is_active) {
            return 0;
        }

        $today   = Carbon::now()->startOfDay();
        $dueDate = $fee->due_date ? Carbon::parse($fee->due_date) : $today->copy();

        $created = 0;

        foreach ($this->matchingEnrollments($fee) as $enrollment) {
            $studentUserId = DB::table('students')->where('id', $enrollment->student_id)->value('user_id');
            if (! $studentUserId) {
                continue; // no login row → nothing to bill against (invoices.student_id = users.id)
            }

            // Duplicate guard: this student was already charged for this incidental.
            $already = Invoice::withoutGlobalScopes()
                ->where('incidental_fee_id', $fee->id)
                ->where('student_enrollment_id', $enrollment->id)
                ->exists();
            if ($already) {
                continue;
            }

            $amount = round((float) $fee->amount, 2);

            $invoice = Invoice::create([
                'invoice_number'        => app(InvoiceService::class)->generateInvoiceNumber((int) $fee->school_id),
                'school_id'             => $fee->school_id,
                'student_id'            => $studentUserId,
                'student_enrollment_id' => $enrollment->id,
                'incidental_fee_id'     => $fee->id,
                'academic_year_id'      => $enrollment->academic_year_id,
                'term_id'               => $enrollment->term_id,
                'subtotal_amount'       => $amount,
                'discount_amount'       => 0,
                'total_amount'          => $amount,
                'paid_amount'           => 0,
                'balance'               => $amount,
                'status'                => Invoice::STATUS_UNPAID,
                'issue_date'            => $today->toDateString(),
                // Billed today so it appears on the Billing page immediately.
                'billing_date'          => $today->toDateString(),
                'due_date'              => $dueDate->toDateString(),
                'issued_by'             => $actorId,
                'notes'                 => 'Incidental: '.$fee->name,
            ]);

            InvoiceItem::create([
                'invoice_id'      => $invoice->id,
                'school_id'       => $fee->school_id,
                'fee_type'        => 'incidental',
                'description'     => $fee->description ? $fee->name.' — '.$fee->description : $fee->name,
                'billing_basis'   => 'fixed',
                'quantity'        => 1,
                'unit_amount'     => $amount,
                'amount'          => $amount,
                'discount_amount' => 0,
                'net_amount'      => $amount,
            ]);

            $this->ledger->postInvoiceCharge($invoice->fresh(), $actorId);
            $created++;
        }

        $fee->forceFill(['charged_at' => Carbon::now()])->save();

        return $created;
    }

    /**
     * Enrolled students whose enrolment matches the fee's scope. A NULL scope
     * axis matches everyone on that axis; education_node matches the node and
     * any of its descendants (so an "Elementary" fee covers every grade under it).
     *
     * @return \Illuminate\Support\Collection<int, StudentEnrollment>
     */
    protected function matchingEnrollments(IncidentalFee $fee): \Illuminate\Support\Collection
    {
        $query = StudentEnrollment::withoutGlobalScopes()
            ->where('school_id', $fee->school_id)
            ->where('status', StudentEnrollment::STATUS_ENROLLED);

        if ($fee->education_node_id) {
            $query->whereIn('education_node_id', $this->subtreeNodeIds((int) $fee->education_node_id));
        }
        if ($fee->program_id) {
            $query->where('program_id', $fee->program_id);
        }
        if ($fee->year_level) {
            $query->where('year_level', (int) $fee->year_level);
        }
        if ($fee->academic_year_id) {
            $query->where('academic_year_id', $fee->academic_year_id);
        }

        return $query->get();
    }

    /**
     * The node id plus every descendant id, walked breadth-first over the
     * education_nodes parent_id tree (school-scoped, cycle-guarded).
     *
     * @return array<int, int>
     */
    protected function subtreeNodeIds(int $rootId): array
    {
        $childrenByParent = DB::table('education_nodes')
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        $ids   = [$rootId];
        $queue = [$rootId];
        for ($i = 0; $i < count($queue) && $i < 5000; $i++) {
            foreach ($childrenByParent->get($queue[$i], collect()) as $child) {
                if (! in_array((int) $child->id, $ids, true)) {
                    $ids[]   = (int) $child->id;
                    $queue[] = (int) $child->id;
                }
            }
        }

        return $ids;
    }
}
