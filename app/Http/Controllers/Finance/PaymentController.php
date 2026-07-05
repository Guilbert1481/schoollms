<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentSubmission;
use App\Models\User;
use App\Services\Payments\PaymentService;
use App\Support\EducationLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
	public function __construct(private readonly PaymentService $paymentService)
	{
	}

	public function index(Request $request)
	{
		$actor    = $request->user();
		$schoolId = (int) $actor->school_id;

		$students = User::query()
			->where('school_id', $schoolId)
			->where('role', 'student')
			->orderBy('last_name')
			->orderBy('first_name')
			->get(['id', 'first_name', 'middle_name', 'last_name', 'email']);

		// ---- Education-level tabs + dropdown filters (Student Ledgers pattern,
		// via the reusable x-table.level-tabs / x-table.filter-toolbar components).
		$levels = DB::table('education_nodes')
			->whereNull('parent_id')
			->where('is_offered', 1)
			->where('is_active', 1)
			->orderBy('order_index')
			->get(['id', 'name']);

		$levelParam    = $request->query('level');
		$showAll       = $levelParam === null || $levelParam === '' || strtolower((string) $levelParam) === 'all';
		$activeLevelId = $showAll ? 0 : (int) $levelParam;

		$activeLevel        = $levels->firstWhere('id', $activeLevelId);
		$singleLevel        = $levels->count() === 1 ? $levels->first() : null;
		$effectiveLevel     = $showAll ? $singleLevel : $activeLevel;
		$activeLevelIsBasic = (bool) ($effectiveLevel && EducationLevels::isBasic($effectiveLevel->name));

		// Education_node ids that roll up to the active level (for SQL filters).
		$nodeToRoot   = EducationLevels::nodeRootMap();
		$levelNodeIds = $activeLevelId
			? array_keys(array_filter($nodeToRoot, fn ($root) => (int) $root === $activeLevelId))
			: [];

		$statusFilter   = $request->query('status') ?: '';
		$statusOptions  = [
			Invoice::STATUS_PAID    => 'Paid',
			Invoice::STATUS_PARTIAL => 'Partial',
			Invoice::STATUS_UNPAID  => 'Unpaid',
		];
		if ($statusFilter !== '' && ! array_key_exists($statusFilter, $statusOptions)) {
			$statusFilter = '';
		}

		$academicYearId = $request->integer('academic_year_id') ?: null;
		$yearLevel      = $request->query('year_level');
		$yearLevel      = ($yearLevel === null || $yearLevel === '') ? null : (string) $yearLevel;
		$programId      = $request->integer('program_id') ?: null;
		$sectionId      = $request->integer('section_id') ?: null;

		$filters = [
			'levelNodeIds'   => $levelNodeIds,
			'status'         => $statusFilter,
			'academicYearId' => $academicYearId,
			'yearLevel'      => $yearLevel,
			'programId'      => $programId,
			'sectionId'      => $sectionId,
		];

		// ---- Filter dropdown options (mirrors the ledger/invoices toolbars).
		$academicYears = DB::table('academic_years')->where('school_id', $schoolId)
			->orderByDesc('start_date')->pluck('name', 'id')->all();

		if ($activeLevelIsBasic) {
			$yearLevelOptions = EducationLevels::basicGradeOptions();
		} elseif ($activeLevelId > 0) {
			$yearLevelOptions = EducationLevels::yearLevelOptions($activeLevelId);
		} else {
			$yearLevelOptions = [];
			for ($g = 1; $g <= 12; $g++) {
				$yearLevelOptions[(string) $g] = 'Grade '.$g;
			}
		}

		// Program filter — higher-ed levels only; Section filter — Basic Ed only.
		$showProgramFilter = ! $showAll && ! $activeLevelIsBasic && $activeLevelId > 0;
		$programOptions = [];
		if ($showProgramFilter) {
			$programOptions = DB::table('programs')
				->where('school_id', $schoolId)
				->orderBy('code')->orderBy('name')
				->get(['id', 'code', 'name', 'education_node_id'])
				->filter(fn ($p) => (int) ($nodeToRoot[$p->education_node_id ?? null] ?? 0) === $activeLevelId)
				->mapWithKeys(fn ($p) => [(int) $p->id => $p->code ?: $p->name])
				->all();
		}

		$sectionOptions = [];
		if ($activeLevelIsBasic) {
			$sectionOptions = DB::table('sections')
				->where('school_id', $schoolId)
				->where('is_active', 1)
				->orderBy('name')
				->pluck('name', 'id')
				->all();
		}

		// Which optional columns the verification table shows is a per-school
		// display rule driven by the Education Structure Tree.
		$roots       = EducationLevels::offeredRoots();
		$showLevel   = $roots->count() > 1;
		$showProgram = $roots->contains(fn ($r) => ! EducationLevels::isBasic($r->name));

		$activeTab = in_array($request->query('tab'), ['payments', 'verification'], true)
			? $request->query('tab')
			: 'payments';

		return view('finance.payment', [
			'students'          => $students,
			'payments'          => $this->recentPaymentRows($schoolId, $filters),
			'paymentTypes'      => Payment::TYPES,
			'pendingRows'       => $this->pendingSubmissionRows($schoolId, $filters),
			'showLevel'         => $showLevel,
			'showProgram'       => $showProgram,

			// Level tabs + filter toolbar state (reusable components).
			'levels'             => $levels,
			'activeLevelId'      => $activeLevelId,
			'showAll'            => $showAll,
			'activeLevelIsBasic' => $activeLevelIsBasic,
			'statusOptions'      => $statusOptions,
			'statusFilter'       => $statusFilter,
			'academicYears'      => $academicYears,
			'academicYearId'     => $academicYearId,
			'yearLevelOptions'   => $yearLevelOptions,
			'yearLevel'          => $yearLevel,
			'programOptions'     => $programOptions,
			'programId'          => $programId,
			'showProgramFilter'  => $showProgramFilter,
			'sectionOptions'     => $sectionOptions,
			'sectionId'          => $sectionId,
			'activeTab'          => $activeTab,
		]);
	}

	public function store(Request $request)
	{
		$actor = $request->user();

		$validated = $request->validate([
			'student_id' => ['required', 'integer', 'exists:users,id'],
			'amount' => ['required', 'numeric', 'min:0.01'],
			'payment_method' => ['required', 'string', 'max:50'],
			'payment_type' => ['required', 'string', 'in:' . implode(',', Payment::TYPES)],
			'reference_number' => ['nullable', 'string', 'max:120'],
		]);

		$student = User::query()
			->where('school_id', $actor->school_id)
			->findOrFail((int) $validated['student_id']);

		$this->paymentService->recordGeneralPayment(
			actor: $actor,
			amount: (float) $validated['amount'],
			paymentMethod: (string) $validated['payment_method'],
			paymentType: (string) $validated['payment_type'],
			referenceNumber: $validated['reference_number'] ?? null,
			studentId: (int) $student->id,
			schoolId: (int) $actor->school_id
		);

		return redirect()
			->route('finance.payments.index')
			->with('success', 'Payment recorded successfully.');
	}

	/**
	 * Approve a pending proof-of-payment: create the real Payment (which posts
	 * the ledger credit and recomputes the invoice) and mark the submission
	 * verified. This is the ONLY path by which an online submission touches the
	 * ledger. Guarded to pending-only + wrapped in a transaction so a partial
	 * failure can never leave a credited-but-unverified (or double-credited) row.
	 */
	public function verify(Request $request, PaymentSubmission $submission)
	{
		$actor = $request->user();
		abort_unless((int) $submission->school_id === (int) $actor->school_id, 404);

		if (! $submission->isPending()) {
			return back()->with('info', 'This payment has already been reviewed.');
		}

		$invoice = $submission->invoice;
		if (! $invoice) {
			return back()->with('error', 'This submission is not linked to an invoice, so it cannot be verified automatically.');
		}

		$posted = DB::transaction(function () use ($actor, $submission, $invoice) {
			// Re-read under a row lock and re-check pending INSIDE the transaction
			// so two finance users clicking Verify at once can't both post a
			// payment for the same proof.
			$locked = PaymentSubmission::query()->whereKey($submission->getKey())->lockForUpdate()->first();
			if (! $locked || $locked->status !== PaymentSubmission::STATUS_PENDING) {
				return false;
			}

			// Credit ONLY the invoice amount — the system fee is a platform
			// convenience charge, not tuition, and never hits the student ledger.
			$payment = $this->paymentService->recordInvoicePayment(
				actor: $actor,
				invoice: $invoice,
				amount: round((float) $locked->amount, 2),
				paymentMethod: (string) $locked->payment_method,
				referenceNumber: $locked->reference_number,
			);

			$locked->update([
				'status'      => PaymentSubmission::STATUS_VERIFIED,
				'reviewed_by' => (int) $actor->id,
				'reviewed_at' => now(),
				'payment_id'  => (int) $payment->id,
			]);

			return true;
		});

		if (! $posted) {
			return back()->with('info', 'This payment has already been reviewed.');
		}

		return back()->with('success', 'Payment verified and posted to '.$invoice->invoice_number.'.');
	}

	/** Decline a pending proof-of-payment (no ledger effect); records the reason. */
	public function reject(Request $request, PaymentSubmission $submission)
	{
		$actor = $request->user();
		abort_unless((int) $submission->school_id === (int) $actor->school_id, 404);

		if (! $submission->isPending()) {
			return back()->with('info', 'This payment has already been reviewed.');
		}

		$data = $request->validate([
			'review_note' => ['nullable', 'string', 'max:255'],
		]);

		$submission->update([
			'status'      => PaymentSubmission::STATUS_REJECTED,
			'reviewed_by' => (int) $actor->id,
			'reviewed_at' => now(),
			'review_note' => $data['review_note'] ?? null,
		]);

		return back()->with('success', 'Payment submission rejected.');
	}

	/**
	 * Apply the shared level/AY/year/program/section (+ optional invoice status)
	 * filters to a query joined as: invoices `i` + student_enrollments `se`.
	 * Rows without the joined data drop out only when a specific filter is set —
	 * same semantics as the other finance toolbars.
	 */
	private function applyEnrollmentFilters($query, array $filters, bool $withStatus)
	{
		return $query
			->when($filters['levelNodeIds'], fn ($q, $ids) => $q->whereIn('se.education_node_id', $ids))
			->when($filters['academicYearId'], fn ($q, $v) => $q->where('i.academic_year_id', $v))
			->when($filters['yearLevel'] !== null, fn ($q) => $q->where('se.year_level', (int) $filters['yearLevel']))
			->when($filters['programId'], fn ($q, $v) => $q->where('se.program_id', $v))
			->when($filters['sectionId'], fn ($q, $v) => $q->where('se.section_id', $v))
			->when($withStatus && $filters['status'] !== '', fn ($q) => $q->where('i.status', $filters['status']));
	}

	/**
	 * Recent (verified) payments for the Payments tab, with the invoice number
	 * they settled. Plain join to match the finance modules' query style.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	private function recentPaymentRows(int $schoolId, array $filters): \Illuminate\Support\Collection
	{
		$raw = DB::table('payments as p')
			->leftJoin('users as u', 'u.id', '=', 'p.student_id')
			->leftJoin('invoices as i', 'i.id', '=', 'p.invoice_id')
			->leftJoin('student_enrollments as se', 'se.id', '=', 'i.student_enrollment_id')
			->where('p.school_id', $schoolId)
			->whereNull('p.training_enrollment_id')
			->tap(fn ($q) => $this->applyEnrollmentFilters($q, $filters, withStatus: true))
			->orderByDesc('p.id')
			->limit(100)
			->get([
				'p.id', 'p.amount', 'p.reference_number', 'p.payment_method', 'p.payment_type',
				'p.paid_at', 'p.created_at',
				'u.first_name', 'u.middle_name', 'u.last_name',
				'i.invoice_number',
			]);

		return $raw->map(function ($r) {
			$name = trim(($r->first_name ?? '').' '.($r->middle_name ? $r->middle_name.' ' : '').($r->last_name ?? ''));
			$date = $r->paid_at ?: $r->created_at;

			return (object) [
				'date'             => $date ? Carbon::parse($date) : null,
				'student_name'     => $name !== '' ? $name : 'N/A',
				'invoice_number'   => $r->invoice_number ?: '—',
				'payment_type'     => (string) $r->payment_type,
				'payment_method'   => (string) $r->payment_method,
				'reference_number' => $r->reference_number,
				'amount'           => (float) $r->amount,
			];
		});
	}

	/**
	 * Build the display rows for the pending proof-of-payment review queue,
	 * resolving each payer's Level / Program / grade from their linked enrolment.
	 * Uses a plain join (rather than relation access) to match the finance
	 * modules' query style and keep static analysis clean.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	private function pendingSubmissionRows(int $schoolId, array $filters): \Illuminate\Support\Collection
	{
		$raw = DB::table('payment_submissions as ps')
			->leftJoin('users as u', 'u.id', '=', 'ps.student_id')
			->leftJoin('invoices as i', 'i.id', '=', 'ps.invoice_id')
			->leftJoin('student_enrollments as se', 'se.id', '=', 'i.student_enrollment_id')
			->leftJoin('programs as p', 'p.id', '=', 'se.program_id')
			->where('ps.school_id', $schoolId)
			->where('ps.status', PaymentSubmission::STATUS_PENDING)
			->tap(fn ($q) => $this->applyEnrollmentFilters($q, $filters, withStatus: false))
			->orderBy('ps.submitted_at') // oldest first — review in the order received
			->orderBy('ps.id')
			->get([
				'ps.id as submission_id', 'ps.student_id', 'ps.amount', 'ps.system_fee',
				'ps.payment_method as method', 'ps.submitted_at', 'ps.proof_path',
				'u.first_name', 'u.middle_name', 'u.last_name',
				'i.invoice_number', 'i.due_date',
				'se.education_node_id', 'se.year_level',
				'p.code as program_code', 'p.name as program_name', 'p.education_node_id as program_node_id',
			]);

		if ($raw->isEmpty()) {
			return collect();
		}

		$nodeToRoot   = EducationLevels::nodeRootMap();
		$rootNameById = DB::table('education_nodes')->whereNull('parent_id')->pluck('name', 'id')->all();

		return $raw->map(function ($r) use ($nodeToRoot, $rootNameById) {
			// Level = root of the enrolment's node (falling back to its program's node).
			$nodeId = $r->education_node_id ?: ($r->program_node_id ?: null);
			$rootId = $nodeId ? ($nodeToRoot[(int) $nodeId] ?? null) : null;
			$rootName = $rootId ? ($rootNameById[$rootId] ?? null) : null;
			$isBasic  = EducationLevels::isBasic($rootName);

			$yl = $r->year_level;
			$gradeYear = ($yl === null || $yl === '')
				? '—'
				: (is_numeric($yl) ? ($isBasic ? 'Grade ' : 'Year ').(int) $yl : (string) $yl);

			$name = trim($r->first_name.' '.($r->middle_name ? $r->middle_name.' ' : '').$r->last_name);

			$programLabel = $isBasic
				? '—'
				: ($r->program_name ? trim(($r->program_code ? $r->program_code.' — ' : '').$r->program_name) : '—');

			return (object) [
				'submission_id'  => (int) $r->submission_id,
				'student_id'     => (int) $r->student_id,
				'name'           => $name !== '' ? $name : '—',
				'level'          => $rootName ?: '—',
				'program'        => $programLabel,
				'grade_year'     => $gradeYear,
				'invoice_number' => $r->invoice_number ?: '—',
				'due_date'       => $r->due_date ? Carbon::parse($r->due_date) : null,
				'proof_url'      => $r->proof_path ? Storage::disk('public')->url($r->proof_path) : null,
				'payment_date'   => $r->submitted_at ? Carbon::parse($r->submitted_at) : null,
				'amount'         => (float) $r->amount,
				'system_fee'     => (float) $r->system_fee,
				'method'         => (string) $r->method,
			];
		});
	}
}
