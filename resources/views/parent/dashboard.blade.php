{{-- Parent portal home: one card per linked child. The selected child drives
     the per-child pages (grades, schedule, billing) as they come online. --}}

@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">My Children</h1>
            <p class="text-sm text-slate-500 mt-1">
                Everyone linked to your parent account — across all schools.
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($cards->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center">
            <i data-lucide="users" class="w-10 h-10 mx-auto text-slate-300"></i>
            <h2 class="mt-4 text-lg font-semibold text-slate-700">No children linked yet</h2>
            <p class="mt-1 text-sm text-slate-500 max-w-md mx-auto">
                When the school links a student to your account you will see them
                here. If you believe this is an error, please contact the school
                registrar.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach ($cards as $card)
                <div class="rounded-2xl border bg-white p-6 transition-all duration-200
                            {{ (int) $activeChildId === (int) $card->id
                                ? 'border-indigo-400 ring-2 ring-indigo-100'
                                : 'border-slate-200 hover:border-slate-300' }}">

                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold shrink-0">
                                {{ strtoupper(substr($card->name, 0, 1) ?: '?') }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-slate-800 truncate">{{ $card->name }}</h3>
                                <p class="text-xs text-slate-500 truncate">{{ $card->student_number }}</p>
                            </div>
                        </div>
                        @if ((int) $activeChildId === (int) $card->id)
                            <span class="shrink-0 text-[11px] font-semibold uppercase tracking-wide text-indigo-600 bg-indigo-50 rounded-full px-2.5 py-1">
                                Selected
                            </span>
                        @endif
                    </div>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-500">School</dt>
                            <dd class="font-medium text-slate-700 text-right truncate">{{ $card->school_name }}</dd>
                        </div>
                        @if ($card->year_level)
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-500">Year / Grade</dt>
                                <dd class="font-medium text-slate-700">{{ $card->year_level }}</dd>
                            </div>
                        @endif
                        @if ($card->status_pill)
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-500">Enrollment</dt>
                                <dd>{!! $card->status_pill !!}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-500">Outstanding</dt>
                            <dd class="font-semibold {{ $card->outstanding > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                &#8369;{{ number_format($card->outstanding, 2) }}
                            </dd>
                        </div>
                        @if ($card->next_due)
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-500">Next Due</dt>
                                <dd class="font-medium text-slate-700">
                                    {{ \Illuminate\Support\Carbon::parse($card->next_due)->format('M d, Y') }}
                                </dd>
                            </div>
                        @endif
                        @if ($card->relationship)
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-500">You are listed as</dt>
                                <dd class="font-medium text-slate-700">{{ $card->relationship }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ((int) $activeChildId !== (int) $card->id)
                        <form method="POST" action="{{ route('parent.children.select', $card->id) }}" class="mt-5">
                            @csrf
                            <button type="submit"
                                class="w-full rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                                Select this child
                            </button>
                        </form>
                    @else
                        <p class="mt-5 text-center text-xs text-slate-400">
                            Grades, schedule, and billing pages for the selected child are coming next.
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
