@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Finance Payments</h1>
        <p class="text-sm text-slate-500">Record non-training payments from a single, shared payment engine.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-1 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-800">Record Payment</h2>

            <form method="POST" action="{{ route('finance.payments.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="student_id" class="mb-1 block text-sm font-medium text-slate-700">Student</label>
                    <select id="student_id" name="student_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <option value="">Select student</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->full_name }} ({{ $student->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="amount" class="mb-1 block text-sm font-medium text-slate-700">Amount</label>
                    <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
                </div>

                <div>
                    <label for="payment_method" class="mb-1 block text-sm font-medium text-slate-700">Payment Method</label>
                    <select id="payment_method" name="payment_method" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <option value="">Select method</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="gcash" {{ old('payment_method') === 'gcash' ? 'selected' : '' }}>GCash</option>
                        <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                    </select>
                </div>

                <div>
                    <label for="payment_type" class="mb-1 block text-sm font-medium text-slate-700">Payment Type</label>
                    <select id="payment_type" name="payment_type" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <option value="">Select type</option>
                        @foreach($paymentTypes as $type)
                            <option value="{{ $type }}" {{ old('payment_type') === $type ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="reference_number" class="mb-1 block text-sm font-medium text-slate-700">Reference Number (optional)</label>
                    <input id="reference_number" name="reference_number" type="text" value="{{ old('reference_number') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
                </div>

                <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Save Payment
                </button>
            </form>
        </div>

        <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-800">Recent Payments</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="px-3 py-2 font-medium">Date</th>
                            <th class="px-3 py-2 font-medium">Student</th>
                            <th class="px-3 py-2 font-medium">Type</th>
                            <th class="px-3 py-2 font-medium">Method</th>
                            <th class="px-3 py-2 font-medium">Reference</th>
                            <th class="px-3 py-2 font-medium text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-2 text-slate-600">{{ optional($payment->paid_at ?? $payment->created_at)->format('M d, Y h:i A') }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $payment->student?->full_name ?? 'N/A' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ ucwords(str_replace('_', ' ', (string) $payment->payment_type)) }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ strtoupper((string) $payment->payment_method) }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $payment->reference_number ?: '-' }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-slate-800">{{ number_format((float) $payment->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-slate-500">No payments recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
