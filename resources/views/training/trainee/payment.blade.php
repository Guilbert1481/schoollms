@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Course Payment</h1>
        <p class="text-sm text-slate-500">Complete payment to activate your enrollment.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Course</p>
                <p class="text-base font-semibold text-slate-800">{{ $course->course_name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Session</p>
                <p class="text-base font-semibold text-slate-800">{{ $session->session_name ?? 'Default Session' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Amount</p>
                <p class="text-base font-semibold text-slate-800">{{ number_format((float) ($course->fee ?? 0), 2) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
                <p class="text-base font-semibold text-amber-700">Pending Payment</p>
            </div>
        </div>

        <form method="POST" action="{{ route('training.trainee.courses.payment.confirm', ['enrollment' => $enrollment->id]) }}" class="mt-6 space-y-4">
            @csrf

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
                <label for="reference_number" class="mb-1 block text-sm font-medium text-slate-700">Reference Number (optional)</label>
                <input id="reference_number" name="reference_number" type="text" value="{{ old('reference_number') }}" placeholder="Transaction reference" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('training.trainee.courses') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</a>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Confirm Payment</button>
            </div>
        </form>
    </div>
</div>
@endsection
