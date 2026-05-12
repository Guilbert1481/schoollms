@extends('layouts.enrollment')

@section('content')
<div class="px-8 py-6 max-w-4xl">

    <div class="text-xs font-extrabold text-slate-500 tracking-widest mb-1">
        STEP 6 OF 7 — REVIEW &amp; SUBMIT
    </div>
    <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden mb-6">
        <div class="h-full bg-indigo-600" style="width:86%"></div>
    </div>

    <h1 class="text-2xl font-extrabold text-slate-800 mb-1">Review your application</h1>
    <p class="text-sm text-slate-500 mb-6">
        Please double-check the details below before final submission.
    </p>

    @if (session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg p-3 mb-4 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @php
        $box = 'border border-slate-200 rounded-xl bg-white p-4 mb-4';
        $hd  = 'text-xs font-extrabold uppercase tracking-widest text-slate-500 mb-2';
        $row = 'flex justify-between border-b border-slate-100 py-1.5 text-sm';
        $lbl = 'text-slate-500';
        $val = 'font-semibold text-slate-800';
    @endphp

    {{-- Personal --}}
    <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-2">
            <div class="{{ $hd }}">Personal Information</div>
            <a href="{{ route('public.apply.show', $term->id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Edit</a>
        </div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Full Name</span>
            <span class="{{ $val }}">{{ trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? '')) ?: '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Date of Birth</span>
            <span class="{{ $val }}">{{ $student->date_of_birth ? \Illuminate\Support\Carbon::parse($student->date_of_birth)->format('M d, Y') : '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Gender</span>
            <span class="{{ $val }}">{{ ucfirst($student->gender ?? '—') }}</span></div>
    </div>

    {{-- Contact --}}
    <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-2">
            <div class="{{ $hd }}">Contact Details</div>
            <a href="{{ route('public.apply.step2', $term->id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Edit</a>
        </div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Email</span>
            <span class="{{ $val }}">{{ $student->user->email ?? '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Mobile</span>
            <span class="{{ $val }}">{{ $student->mobile_number ?? $student->phone ?? '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Address</span>
            <span class="{{ $val }}">{{ $student->address ?? '—' }}</span></div>
    </div>

    {{-- Pathway --}}
    <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-2">
            <div class="{{ $hd }}">Learning Pathway</div>
            <a href="{{ route('public.apply.pathway', $term->id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Edit</a>
        </div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Education Level</span>
            <span class="{{ $val }}">{{ $node?->name ?? '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Programme</span>
            <span class="{{ $val }}">{{ $program ? ($program->code ? $program->code.' — ' : '').$program->name : '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Year Level</span>
            <span class="{{ $val }}">{{ $pathway['year_level'] ?? '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Modality</span>
            <span class="{{ $val }}">{{ $modality?->name ?? '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Student Type</span>
            <span class="{{ $val }}">
                @if ($modality && $modality->code === 'async_online')
                    <em class="text-slate-400 font-normal">N/A — Asynchronous Online</em>
                @else
                    {{ ucfirst($pathway['student_type'] ?? '—') }}
                @endif
            </span>
        </div>
    </div>

    {{-- Academic Background --}}
    <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-2">
            <div class="{{ $hd }}">Academic Background</div>
            @if (! $skipped)
                <a href="{{ route('public.apply.academic', $term->id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Edit</a>
            @endif
        </div>
        @if ($skipped)
            <p class="text-sm text-slate-500 italic">Skipped — Regular students reuse their prior academic record.</p>
        @elseif ($backgrounds->isEmpty())
            <p class="text-sm text-slate-500 italic">No academic backgrounds entered.</p>
        @else
            <div class="space-y-2">
                @foreach ($backgrounds as $bg)
                    <div class="border border-slate-100 rounded-lg p-2 text-sm">
                        <div class="font-bold">{{ $bg->school_name }}
                            <span class="text-xs text-slate-500 font-normal">({{ ucfirst(str_replace('_',' ', $bg->education_level)) }})</span>
                        </div>
                        <div class="text-xs text-slate-500">
                            {{ $bg->year_started ?? '?' }} – {{ $bg->year_ended ?? '?' }}
                            @if ($bg->school_address) · {{ $bg->school_address }} @endif
                            @if ($bg->gpa) · GPA: {{ $bg->gpa }} @endif
                            @if ($bg->honors) · {{ $bg->honors }} @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('public.apply.submit', $term->id) }}" class="flex items-center justify-between pt-2">
        @csrf
        <a href="{{ $skipped ? route('public.apply.pathway', $term->id) : route('public.apply.academic', $term->id) }}"
           class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-bold">← Back</a>

        <button type="submit"
                class="px-7 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold shadow-lg">
            Submit Application ✓
        </button>
    </form>
</div>
@endsection
