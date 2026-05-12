{{--
    Contact Details — Step 2 of the enrolment wizard.
    The address form is rendered by a React component (react-hook-form +
    react-select), which submits a hidden HTML form to $action so the existing
    server-side redirect chain still works.
--}}
@php
    $student     = $student     ?? (auth()->user()->student ?? null);
    $stepLabel   = $stepLabel   ?? null;
    $progressPct = $progressPct ?? 28;
    $backUrl     = $backUrl     ?? null;

    $initial = [
        'mobile_number'     => old('mobile_number',     $student->mobile_number   ?? ''),
        'email'             => old('email',             $student->email ?? auth()->user()->email ?? ''),
        'country'           => old('country',           $student->country         ?? 'PH'),
        'country_code'      => old('country_code',      $student->country_code    ?? '+63'),
        'region'            => old('region',            $student->region          ?? ''),
        'province'          => old('province',          $student->province        ?? ''),
        'city_municipality' => old('city_municipality', $student->city_municipality ?? ''),
        'barangay'          => old('barangay',          $student->barangay        ?? ''),
        'zip_code'          => old('zip_code',          $student->zip_code        ?? ''),
        'address_line_1'    => old('address_line_1',    $student->address_line_1  ?? ''),
        'address_line_2'    => old('address_line_2',    $student->address_line_2  ?? ''),
    ];

    $reactProps = [
        'action'  => $action,
        'csrf'    => csrf_token(),
        'backUrl' => $backUrl,
        'initial' => $initial,
    ];
@endphp

<div class="form-container max-w-5xl">
    @if ($stepLabel)
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ $stepLabel }}</div>
        <div class="mb-8 h-1 rounded-full bg-slate-200">
            <div class="h-full rounded-full bg-indigo-600" style="width: {{ $progressPct }}%"></div>
        </div>
    @endif

    <h3 class="mb-6 text-xl font-bold text-slate-900">Contact Details</h3>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div id="contact-details-react-root"
         data-props='@json($reactProps)'></div>
</div>

@push('scripts')
    <script>window.__APP_URL__ = @json(url('/'));</script>
    @vite(['resources/js/contact-address/main.jsx'])
@endpush
