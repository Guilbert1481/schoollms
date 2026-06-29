<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
        <i data-lucide="user" class="h-4 w-4 text-indigo-600"></i>
        <h3 class="text-sm font-bold text-slate-800">Personal Information</h3>
    </div>

    @php
        $isBasic = $header['is_basic'] ?? true;
        $left = [
            ['Full Name', $profile['full_name']],
            ['Date of Birth', $profile['date_of_birth']],
            ['Place of Birth', $profile['place_of_birth']],
            ['Gender', $profile['gender']],
            ['Nationality', $profile['nationality']],
            ['Religion', $profile['religion']],
            ['Blood Type', $profile['blood_type']],
        ];
        // LRN only for Basic Education; "Grade" vs "Year" follows the level.
        // Email Address now lives in the Contact Information table.
        $right = array_values(array_filter([
            $isBasic ? ['LRN', $profile['lrn'], false] : null,
            ['Student ID', $profile['student_id'], true],
            [$isBasic ? 'Current Grade Level' : 'Current Year Level', $profile['grade_level'], false],
            // Basic Ed shows Section; higher-ed shows Program.
            $isBasic ? ['Section', $profile['section'], false] : ['Program', $profile['program'], false],
            ['Academic Year', $profile['academic_year'], false],
            ['Status', $profile['status'], false],
            ['Date of Registration', $profile['date_of_registration'], false],
        ]));
    @endphp

    <div class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
        <div class="space-y-3">
            @foreach($left as [$label, $value])
                <div>
                    <div class="text-[11px] font-semibold text-indigo-500">{{ $label }}</div>
                    <div class="text-sm font-semibold text-slate-800">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        <div class="space-y-3">
            @foreach($right as [$label, $value, $accent])
                <div>
                    <div class="text-[11px] font-semibold text-indigo-500">{{ $label }}</div>
                    @if($label === 'Status' && ($header['status_key'] ?? null))
                        {!! \App\Support\EnrollmentStatuses::pill($header['status_key']) !!}
                    @else
                        <div class="text-sm font-semibold {{ $accent ? 'text-indigo-600' : 'text-slate-800' }}">{{ $value }}</div>
                    @endif
                    {{-- Term/semester under the Academic Year (higher-ed only). --}}
                    @if($label === 'Academic Year' && ! $isBasic && ($profile['term'] ?? null))
                        <div class="text-xs text-slate-500">{{ $profile['term'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
