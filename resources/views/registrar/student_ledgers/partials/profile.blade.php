{{-- Profile tab. A top row of columns (depends on the Student ID orientation),
     then Contact Information and Parents / Guardians stretched to full width.
     Plain CSS (not Tailwind grid utilities) so the layout renders even if the
     Tailwind build hasn't compiled those exact classes.
       portrait  -> Personal | Digital ID | Quick/Emergency      (equal-height)
       landscape -> Personal | (Digital ID, then Emergency|Quick) (natural height) --}}
@php $landscape = ($idCard['orientation'] ?? 'portrait') === 'landscape'; @endphp

<style>
    .sl-profile { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); }
    .sl-profile--portrait  { align-items: stretch; }
    .sl-profile--landscape { align-items: start; }
    .sl-profile__col { display: flex; flex-direction: column; gap: 1.5rem; min-width: 0; }
    .sl-profile-stack { display: flex; flex-direction: column; gap: 1.5rem; margin-top: 1.5rem; }
    .sl-profile-pair { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 1024px) {
        .sl-profile--portrait  { grid-template-columns: minmax(0, 1.85fr) minmax(0, 1.15fr) minmax(0, 1fr); }
        .sl-profile--landscape { grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); }
        /* Portrait: equal-height columns; the last card in each fills the
           remaining space so the column bottoms line up. */
        .sl-profile--portrait .sl-profile__col > :last-child { flex: 1 1 auto; }
        /* Landscape: Emergency Contact sits beside Quick Information. */
        .sl-profile-pair { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); align-items: start; }
    }
</style>

{{-- Top row --}}
<div class="sl-profile {{ $landscape ? 'sl-profile--landscape' : 'sl-profile--portrait' }}">
    @if($landscape)
        <div class="sl-profile__col">
            @include('registrar.student_ledgers.partials.profile._personal')
        </div>
        <div class="sl-profile__col">
            @include('registrar.student_ledgers.partials.profile._id_card')
            <div class="sl-profile-pair">
                @include('registrar.student_ledgers.partials.profile._emergency')
                @include('registrar.student_ledgers.partials.profile._quick')
            </div>
        </div>
    @else
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
    @endif
</div>

{{-- Full-width sections --}}
<div class="sl-profile-stack">
    @include('registrar.student_ledgers.partials.profile._contact')
    @include('registrar.student_ledgers.partials.profile._parents')
</div>
