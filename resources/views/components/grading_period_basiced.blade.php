{{--
    grading_period_basiced — the basic-ed grading-period picker.

    Renders a labelled <select> of the school's configured grading periods
    (values are the ordinal 1..N; labels are the Principal-set names from
    Settings → Grades → Division). Read-only: falls back to the defaults when a
    school has not customised them, so it is safe to render on any page.

    Usage:
        <x-grading_period_basiced name="period" :selected="$context['period']" :auto-submit="true" />
        <x-grading_period_basiced name="grading_period" :selected="old('grading_period')" />

    Props:
        name        request field name (default "period")
        selected    the currently-selected ordinal
        auto-submit submit the closest form on change (for GET filters)
        show-label  render the "<noun>" caption above the select (default true)
        label       override the caption text (defaults to the school's noun)
        include-all prepend an "All <periods>" option (value "") — for filters
        setting     optional pre-resolved GradeSetting to avoid a repeat lookup
--}}
@props([
    'name' => 'period',
    'id' => null,
    'selected' => null,
    'autoSubmit' => false,
    'showLabel' => true,
    'includeAll' => false,
    'label' => null,
    'setting' => null,
])

@php
    // Read-only resolution — never write a row from a render path.
    $gp = $setting;
    if ($gp === null) {
        $gpSchoolId = (int) (auth()->user()?->school_id ?? 0);
        $gp = $gpSchoolId
            ? \App\Models\GradeSetting::where('school_id', $gpSchoolId)->first()
            : null;
    }

    $gpPeriods = $gp ? $gp->periods() : \App\Models\GradeSetting::defaultPeriods();
    $gpLabel = $label ?? ($gp ? $gp->periodLabel() : \App\Models\GradeSetting::DEFAULT_PERIOD_LABEL);
    $gpId = $id ?? $name;
    // Treat null / '' (e.g. an unset filter) as "nothing selected".
    $gpSelected = ($selected !== null && $selected !== '') ? (int) $selected : null;
@endphp

<div>
    @if($showLabel)
        <label for="{{ $gpId }}" class="mb-1 block text-xs font-medium text-slate-600">{{ $gpLabel }}</label>
    @endif
    <select
        name="{{ $name }}"
        id="{{ $gpId }}"
        @if($autoSubmit) onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" @endif
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100']) }}>
        @if($includeAll)
            <option value="">All {{ \Illuminate\Support\Str::plural(\Illuminate\Support\Str::lower($gpLabel)) }}</option>
        @endif
        @foreach($gpPeriods as $ordinal => $periodName)
            <option value="{{ $ordinal }}" @selected($gpSelected === $ordinal)>{{ $periodName }}</option>
        @endforeach
    </select>
</div>
