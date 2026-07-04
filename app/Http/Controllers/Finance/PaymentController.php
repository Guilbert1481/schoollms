<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
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

		$payments = Payment::query()
			->with('student:id,first_name,middle_name,last_name,email')
			->where('school_id', $schoolId)
			->whereNull('training_enrollment_id')
			->latest('id')
			->limit(20)
			->get();

		// Which optional columns to show is a per-school display rule driven by
		// the Education Structure Tree: Level only matters when >1 level is
		// offered; Program only matters when a non-Basic-Ed level is offered.
		$roots       = EducationLevels::offeredRoots();
		$showLevel   = $roots->count() > 1;
		$showProgram = $roots->contains(fn ($r) => ! EducationLevels::isBasic($r->name));

		return view('finance.payment', [
			'students'     => $students,
			'payments'     => $payments,
			'paymentTypes' => Payment::TYPES,
			'pendingRows'  => $this->pendingSubmissionRows($schoolId),
			'showLevel'    => $showLevel,
			'showProgram'  => $showProgram,
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
	 * Build the display rows for the pending proof-of-payment review queue,
	 * resolving each payer's Level / Program / grade from their linked enrolment.
	 * Uses a plain join (rather than relation access) to match the finance
	 * modules' query style and keep static analysis clean.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	private function pendingSubmissionRows(int $schoolId): \Illuminate\Support\Collection
	{
		$raw = DB::table('payment_submissions as ps')
			->leftJoin('users as u', 'u.id', '=', 'ps.student_id')
			->leftJoin('invoices as i', 'i.id', '=', 'ps.invoice_id')
			->leftJoin('student_enrollments as se', 'se.id', '=', 'i.student_enrollment_id')
			->leftJoin('programs as p', 'p.id', '=', 'se.program_id')
			->where('ps.school_id', $schoolId)
			->where('ps.status', PaymentSubmission::STATUS_PENDING)
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
