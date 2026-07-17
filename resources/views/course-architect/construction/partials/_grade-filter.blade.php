{{--
    Grade/year-level dropdown for the Lesson Studio subjects list. Rendered
    beside the table Filter field (list view) and the view toggle (card view).
    Options are the offered stages inside the active stage-group tab
    (Elementary → Grade 1..6); picking one reloads with ?grade=<name>.
    Renders nothing on "All Levels" or when the group has no stages.
--}}
@if($level === 0 && ! $showAll && count($gradeOptions))
    <select onchange="location.href = this.value"
            class="border border-gray-300 rounded px-3 py-2 text-sm bg-white text-slate-700">
        <option value="{{ route('course-architect.lesson-studio.index', ['level' => $activeLevelId]) }}"
                @selected($activeGrade === '')>
            All Grade Levels
        </option>
        @foreach($gradeOptions as $g)
            <option value="{{ route('course-architect.lesson-studio.index', ['level' => $activeLevelId, 'grade' => $g]) }}"
                    @selected($activeGrade === $g)>
                {{ $g }}
            </option>
        @endforeach
    </select>
@endif
