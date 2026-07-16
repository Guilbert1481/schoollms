@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">
    <div class="mx-auto mt-10 max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        @if ($ok)
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                <svg class="h-7 w-7 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-slate-800">Checked in</h1>
        @else
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-rose-100">
                <svg class="h-7 w-7 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-slate-800">Not recorded</h1>
        @endif

        <p class="mt-2 text-sm text-slate-500">{{ $message }}</p>

        <a href="{{ route('student.dashboard') }}" class="mt-6 inline-block rounded-lg bg-slate-800 px-5 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Back to dashboard
        </a>
    </div>
</div>
@endsection
