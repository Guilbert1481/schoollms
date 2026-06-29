{{-- Profile tab. Layout depends on the Student ID orientation:
     portrait  -> 3 columns (Personal/Contact/Parents | ID | Quick/Emergency)
     landscape -> 2 columns (Personal/Contact/Parents | ID + Quick + Emergency) --}}
@php $landscape = ($idCard['orientation'] ?? 'portrait') === 'landscape'; @endphp

@if($landscape)
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-7">
            @include('registrar.student_ledgers.partials.profile._personal')
            @include('registrar.student_ledgers.partials.profile._contact')
            @include('registrar.student_ledgers.partials.profile._parents')
        </div>
        <div class="space-y-6 lg:col-span-5">
            @include('registrar.student_ledgers.partials.profile._id_card')
            @include('registrar.student_ledgers.partials.profile._quick')
            @include('registrar.student_ledgers.partials.profile._emergency')
        </div>
    </div>
@else
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-6">
            @include('registrar.student_ledgers.partials.profile._personal')
            @include('registrar.student_ledgers.partials.profile._contact')
            @include('registrar.student_ledgers.partials.profile._parents')
        </div>
        <div class="lg:col-span-3">
            @include('registrar.student_ledgers.partials.profile._id_card')
        </div>
        <div class="space-y-6 lg:col-span-3">
            @include('registrar.student_ledgers.partials.profile._quick')
            @include('registrar.student_ledgers.partials.profile._emergency')
        </div>
    </div>
@endif
