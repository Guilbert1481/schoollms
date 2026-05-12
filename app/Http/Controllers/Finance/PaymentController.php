<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
	public function __construct(private readonly PaymentService $paymentService)
	{
	}

	public function index(Request $request)
	{
		$actor = $request->user();

		$students = User::query()
			->where('school_id', $actor->school_id)
			->where('role', 'student')
			->orderBy('last_name')
			->orderBy('first_name')
			->get(['id', 'first_name', 'middle_name', 'last_name', 'email']);

		$payments = Payment::query()
			->with('student:id,first_name,middle_name,last_name,email')
			->where('school_id', $actor->school_id)
			->whereNull('training_enrollment_id')
			->latest('id')
			->limit(20)
			->get();

		return view('finance.payment', [
			'students' => $students,
			'payments' => $payments,
			'paymentTypes' => Payment::TYPES,
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
}

