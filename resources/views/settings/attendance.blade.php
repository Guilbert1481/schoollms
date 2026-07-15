@extends('layouts.app')

@section('content')
@php($bandLabel = $band === 'basic' ? 'Basic Education' : 'Higher Education')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Attendance Settings</h1>
        <p class="text-sm text-slate-500">
            How attendance is captured and scored for each {{ $bandLabel }} level.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if ($levels->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
            No {{ strtolower($bandLabel) }} levels are set up for this school yet.
        </div>
    @else
        <form method="POST" action="{{ route($updateRoute) }}" class="space-y-4">
            @csrf

            @foreach ($levels as $row)
                @php($level = $row['level'])
                @php($s = $row['setting'])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-slate-800">{{ $level->name }}</h2>
                        <span class="text-xs uppercase tracking-wider text-slate-400">{{ $bandLabel }}</span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {{-- Capture mode --}}
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Capture mode</label>
                            <select name="settings[{{ $level->id }}][capture_mode]"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="daily" @selected($s->capture_mode === 'daily')>Daily (homeroom)</option>
                                <option value="session" @selected($s->capture_mode === 'session')>Per subject (session)</option>
                            </select>
                        </div>

                        {{-- Late threshold --}}
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Late after</label>
                            <input type="time" name="settings[{{ $level->id }}][late_after]"
                                value="{{ $s->late_after ? \Illuminate\Support\Str::substr($s->late_after, 0, 5) : '' }}"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>

                        {{-- Grace minutes --}}
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Grace (minutes)</label>
                            <input type="number" min="0" max="240" name="settings[{{ $level->id }}][grace_minutes]"
                                value="{{ (int) $s->grace_minutes }}"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>

                        {{-- Expected days (basic-ed denominator) --}}
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Expected days / grading period</label>
                            <input type="number" min="1" max="250" name="settings[{{ $level->id }}][expected_days_per_period]"
                                value="{{ $s->expected_days_per_period }}"
                                placeholder="Daily mode only"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>

                        {{-- QR rotation --}}
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">QR rotation (seconds)</label>
                            <input type="number" min="3" max="120" name="settings[{{ $level->id }}][qr_rotation_seconds]"
                                value="{{ (int) $s->qr_rotation_seconds }}"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>

                        {{-- Half day --}}
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" value="1" name="settings[{{ $level->id }}][half_day_enabled]"
                                    @checked($s->half_day_enabled)
                                    class="rounded border-slate-300">
                                Allow half-day
                            </label>
                        </div>
                    </div>

                    {{-- Capture methods --}}
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <span class="mb-2 block text-xs font-medium text-slate-600">Enabled capture methods</span>
                        <div class="flex flex-wrap gap-4 text-sm text-slate-700">
                            @foreach (['allow_manual' => 'Manual', 'allow_qr' => 'QR scan', 'allow_biometric' => 'Biometric', 'allow_facial' => 'Facial'] as $field => $label)
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" value="1" name="settings[{{ $level->id }}][{{ $field }}]"
                                        @checked($s->{$field})
                                        class="rounded border-slate-300">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                    Save all levels
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
