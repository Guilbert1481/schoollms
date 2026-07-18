{{--
    Level-aware filter row for the Lesson Studio subjects list. Rendered beside the
    table Filter field (list view) and the view toggle (card view).

    Follows the house convention of the education-level tabs pattern: the Basic
    Education tab filters by GRADE, a higher-ed tab filters by YEAR LEVEL and adds a
    PROGRAM dropdown. Renders nothing on "All Levels" — level tabs deliberately reset
    the other filters, so there is nothing to scope until a tab is chosen.
--}}
@if($level === 0 && ! $showAll)
    @php
        $base = ['level' => $activeLevelId];
    @endphp

    @if(count($gradeOptions))
        <select onchange="location.href = this.value"
                class="border border-gray-300 rounded px-3 py-2 text-sm bg-white text-slate-700">
            <option value="{{ route('course-architect.lesson-studio.index', $base + ($activeProgram !== '' ? ['program' => $activeProgram] : [])) }}"
                    @selected($activeGrade === '')>
                {{ $activeRootIsBasic ? 'All Grade Levels' : 'All Year Levels' }}
            </option>
            @foreach($gradeOptions as $g)
                <option value="{{ route('course-architect.lesson-studio.index', $base + ['grade' => $g] + ($activeProgram !== '' ? ['program' => $activeProgram] : [])) }}"
                        @selected($activeGrade === $g)>
                    {{ $g }}
                </option>
            @endforeach
        </select>
    @endif

    @if(! $activeRootIsBasic && $programOptions->count())
        <select onchange="location.href = this.value"
                class="border border-gray-300 rounded px-3 py-2 text-sm bg-white text-slate-700">
            <option value="{{ route('course-architect.lesson-studio.index', $base + ($activeGrade !== '' ? ['grade' => $activeGrade] : [])) }}"
                    @selected($activeProgram === '')>
                All Programs
            </option>
            @foreach($programOptions as $p)
                <option value="{{ route('course-architect.lesson-studio.index', $base + ['program' => $p->id] + ($activeGrade !== '' ? ['grade' => $activeGrade] : [])) }}"
                        @selected($activeProgram === (string) $p->id)>
                    {{ $p->name }}
                </option>
            @endforeach
        </select>
    @endif
@endif
