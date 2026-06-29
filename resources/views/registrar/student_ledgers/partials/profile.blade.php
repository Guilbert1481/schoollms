{{-- Profile tab. A top section (depends on the Student ID orientation), then
     Contact Information and Parents / Guardians stretched to full width.
     Plain CSS (not Tailwind grid utilities) so the layout renders even if the
     Tailwind build hasn't compiled those exact classes.
       portrait  -> 3 columns: Personal | Digital ID | Quick/Emergency
       landscape -> 2x2 grid:  Personal | Digital ID
                               Emergency | Quick --}}
@php $landscape = ($idCard['orientation'] ?? 'portrait') === 'landscape'; @endphp

<style>
    .sl-profile__col { display: flex; flex-direction: column; gap: 1.5rem; min-width: 0; }
    .sl-profile-stack { display: flex; flex-direction: column; gap: 1.5rem; margin-top: 1.5rem; }

    .sl-portrait  { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); align-items: stretch; }
    .sl-landscape { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); }

    @media (min-width: 1024px) {
        .sl-portrait  { grid-template-columns: minmax(0, 1.85fr) minmax(0, 1.15fr) minmax(0, 1fr); }
        /* Portrait: equal-height columns, last card fills so bottoms line up. */
        .sl-portrait .sl-profile__col > :last-child { flex: 1 1 auto; }

        /* Landscape 2x2: Personal | ID (row 1), Emergency | Quick (row 2).
           The ID (child 2) stretches to match the tall Personal card and centers
           its content; the second row keeps natural heights. */
        .sl-landscape { grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); align-items: stretch; }
        .sl-landscape > :nth-child(3),
        .sl-landscape > :nth-child(4) { align-self: start; }
    }
</style>

@if($landscape)
    <div class="sl-landscape">
        @include('registrar.student_ledgers.partials.profile._personal')
        @include('registrar.student_ledgers.partials.profile._id_card')
        @include('registrar.student_ledgers.partials.profile._emergency')
        @include('registrar.student_ledgers.partials.profile._quick')
    </div>
@else
    <div class="sl-portrait">
        <div class="sl-profile__col">
            @include('registrar.student_ledgers.partials.profile._personal')
        </div>
        <div class="sl-profile__col">
            @include('registrar.student_ledgers.partials.profile._id_card')
        </div>
        <div class="sl-profile__col">
            @include('registrar.student_ledgers.partials.profile._quick')
            @include('registrar.student_ledgers.partials.profile._emergency')
        </div>
    </div>
@endif

{{-- Full-width sections --}}
<div class="sl-profile-stack">
    @include('registrar.student_ledgers.partials.profile._contact')
    @include('registrar.student_ledgers.partials.profile._parents')
</div>
