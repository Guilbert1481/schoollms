{{-- Profile tab. Layout depends on the Student ID orientation:
     portrait  -> 3 columns (Personal/Contact/Parents | ID | Quick/Emergency)
     landscape -> 2 columns (Personal/Contact/Parents | ID + Quick + Emergency)
     Uses plain CSS (not Tailwind grid utilities) so the multi-column layout
     renders even if the Tailwind build hasn't compiled those exact classes. --}}
@php $landscape = ($idCard['orientation'] ?? 'portrait') === 'landscape'; @endphp

<style>
    .sl-profile { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); align-items: stretch; }
    .sl-profile__col { display: flex; flex-direction: column; gap: 1.5rem; min-width: 0; }
    @media (min-width: 1024px) {
        .sl-profile--portrait  { grid-template-columns: minmax(0, 1.85fr) minmax(0, 1.15fr) minmax(0, 1fr); }
        .sl-profile--landscape { grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); }
        /* Equal-height columns: the last card in each column fills the remaining
           space so every column's bottom edge lines up. */
        .sl-profile__col > :last-child { flex: 1 1 auto; }
    }
</style>

<div class="sl-profile {{ $landscape ? 'sl-profile--landscape' : 'sl-profile--portrait' }}">
    @if($landscape)
        <div class="sl-profile__col">
            @include('registrar.student_ledgers.partials.profile._personal')
            @include('registrar.student_ledgers.partials.profile._contact')
            @include('registrar.student_ledgers.partials.profile._parents')
        </div>
        <div class="sl-profile__col">
            @include('registrar.student_ledgers.partials.profile._id_card')
            @include('registrar.student_ledgers.partials.profile._quick')
            @include('registrar.student_ledgers.partials.profile._emergency')
        </div>
    @else
        <div class="sl-profile__col">
            @include('registrar.student_ledgers.partials.profile._personal')
            @include('registrar.student_ledgers.partials.profile._contact')
            @include('registrar.student_ledgers.partials.profile._parents')
        </div>
        <div class="sl-profile__col">
            @include('registrar.student_ledgers.partials.profile._id_card')
        </div>
        <div class="sl-profile__col">
            @include('registrar.student_ledgers.partials.profile._quick')
            @include('registrar.student_ledgers.partials.profile._emergency')
        </div>
    @endif
</div>
