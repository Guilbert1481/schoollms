<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\StudentEnrollment;
use App\Services\Enrollment\EnrollmentActivationService;
use App\Support\EnrollmentStatuses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Finance's enrollment decision queue: every gate-approved enrollment that
 * is not yet official (sent to billing / partially paid / provisionally
 * enrolled). Payments normally advance these automatically; this page lets
 * the finance manager decide manually — officially enroll, provisionally
 * enroll or reject — with or without payment (scholarships, promissory
 * notes, discretion). Every decision is audited.
 */
class EnrollmentQueueController extends Controller
{
    /** Statuses that appear in (and may be decided from) the queue. */
    private const QUEUE_STATUSES = [
        StudentEnrollment::STATUS_SENT_BILLING,
        StudentEnrollment::STATUS_BILLED,
        StudentEnrollment::STATUS_PARTIALLY_PAID,
        StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED,
    ];

    public function __construct(private readonly EnrollmentActivationService $activation)
    {
    }

    public function index(Request $request)
    {
        $schoolId = (int) (auth()->user()->school_id ?? 0);

        // Joined read (house pattern, see LedgerController) — no relation
        // property access, keeps Larastan level 4 clean.
        $enrollments = DB::table('student_enrollments as se')
            ->leftJoin('students as st', 'st.id', '=', 'se.student_id')
            ->leftJoin('programs as p', 'p.id', '=', 'se.program_id')
            ->where('se.school_id', $schoolId)
            ->whereIn('se.status', self::QUEUE_STATUSES)
            ->orderByRaw('se.payment_due_at is null')
            ->orderBy('se.payment_due_at')
            ->orderByDesc('se.id')
            ->get([
                'se.id', 'se.status', 'se.billing_cleared_as', 'se.payment_due_at', 'se.education_level',
                'st.first_name', 'st.last_name', 'st.student_number',
                'p.name as program_name',
            ]);

        // First-due invoice per enrollment, one query for the whole queue.
        $firstInvoices = DB::table('invoices')
            ->where('school_id', $schoolId)
            ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
            ->orderBy('billing_date')
            ->orderBy('id')
            ->get(['student_enrollment_id', 'total_amount', 'balance'])
            ->groupBy('student_enrollment_id')
            ->map(fn ($group) => $group->first());

        $rows = $enrollments->map(function ($e) use ($firstInvoices) {
            $name = trim(($e->last_name ?? '').', '.($e->first_name ?? ''), ', ') ?: '—';

            $docsComplied = $e->billing_cleared_as === 'assessed';

            return [
                'student' => '<div class="font-semibold text-gray-800 dark:text-gray-100">'.e($name).'</div>'
                    .($e->student_number ? '<div class="text-xs text-gray-400">'.e($e->student_number).'</div>' : ''),
                'program' => e($e->program_name
                    ?? ucwords(str_replace('_', ' ', (string) ($e->education_level ?: '—')))),
                'docs' => $docsComplied
                    ? '<span class="text-xs font-bold text-emerald-600">Complied</span>'
                    : '<span class="text-xs font-bold text-amber-600">Incomplete</span>',
                'status'        => EnrollmentStatuses::pillForDb($e->status),
                'first_invoice' => $this->firstInvoiceCell($firstInvoices->get($e->id)),
                'due'           => $e->payment_due_at
                    ? \Illuminate\Support\Carbon::parse($e->payment_due_at)->format('M d, Y')
                    : '—',
                'actions'       => '<button type="button" '
                    .'onclick="openEnrollmentDecision('.(int) $e->id.', \''.e(addslashes($name)).'\')" '
                    .'class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700">'
                    .'<i data-lucide="gavel" class="h-3.5 w-3.5"></i> Decide</button>',
            ];
        })->values()->all();

        $columns = [
            ['key' => 'student',       'label' => 'Student',        'width' => '220px', 'raw' => true],
            ['key' => 'program',       'label' => 'Program / Level', 'width' => '180px'],
            ['key' => 'docs',          'label' => 'Documents',      'width' => '110px', 'raw' => true],
            ['key' => 'status',        'label' => 'Status',         'width' => '150px', 'raw' => true],
            ['key' => 'first_invoice', 'label' => 'First Invoice',  'width' => '170px', 'raw' => true],
            ['key' => 'due',           'label' => 'Payment Due',    'width' => '120px'],
            ['key' => 'actions',       'label' => 'Actions',        'width' => '110px', 'raw' => true],
        ];

        return view('finance.enrollment_queue', [
            'columns' => $columns,
            'rows'    => $rows,
        ]);
    }

    /** Manual finance decision on one enrollment. */
    public function decide(Request $request, StudentEnrollment $enrollment)
    {
        abort_unless((int) $enrollment->school_id === (int) auth()->user()->school_id, 404);

        $data = $request->validate([
            'decision' => ['required', 'in:officially,provisionally,rejected'],
            'note'     => ['nullable', 'string', 'max:500', 'required_if:decision,rejected'],
        ], [
            'note.required_if' => 'A reason is required when rejecting an enrollment.',
        ]);

        if (! in_array($enrollment->status, self::QUEUE_STATUSES, true)) {
            return back()->with('info', 'This enrollment is no longer awaiting a decision.');
        }

        $status = $this->activation->decide(
            $enrollment,
            $data['decision'],
            (int) auth()->id(),
            $data['note'] ?? null
        );

        return back()->with('success', 'Enrollment marked as '.ucwords(str_replace('_', ' ', $status)).'.');
    }

    private function firstInvoiceCell(?object $invoice): string
    {
        if (! $invoice) {
            return '<span class="text-xs text-gray-400">No invoice yet</span>';
        }

        $balance = (float) $invoice->balance;
        $total   = (float) $invoice->total_amount;

        if ($balance <= 0.005) {
            return '<span class="text-xs font-bold text-emerald-600">Settled</span>';
        }
        if ($balance < $total - 0.005) {
            return '<span class="text-xs font-bold text-amber-600">₱'.number_format($balance, 2).' remaining</span>';
        }

        return '<span class="text-xs font-bold text-gray-500">₱'.number_format($balance, 2).' unpaid</span>';
    }
}
