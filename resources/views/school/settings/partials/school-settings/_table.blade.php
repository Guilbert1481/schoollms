<section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-lg font-extrabold text-slate-800 dark:text-white">
            Academic Years & Terms
        </h2>

        <div class="flex items-center gap-2">
            <input type="text"
                   id="ayFilter"
                   placeholder="Filter academic years..."
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-64">

            <button type="button"
                    onclick="openAYColumnSettings()"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:bg-slate-50 font-semibold">
                Columns
            </button>

            <button type="button"
                    class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold"
                    onclick="openCreateAYModal()">
                Create Academic Year
            </button>
        </div>
    </div>

    <div class="w-full">
        <table id="ayTermsTable" class="w-full text-sm table-fixed">
            <thead>
                <tr class="text-left text-slate-500">
                    <th class="py-2 pr-4 w-[25%]" data-ay-col="name">Academic Year / Term</th>
                    <th class="py-2 pr-4 w-[20%]" data-ay-col="start">Start</th>
                    <th class="py-2 pr-4 w-[20%]" data-ay-col="end">End</th>
                    <th class="py-2 pr-4 w-[15%]" data-ay-col="status">Status</th>
                    <th class="py-2 pr-4 w-[20%]" data-ay-col="actions">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($academicYears as $ay)

                    {{-- Academic Year Row --}}
                    <tr class="ay-row bg-slate-50/60 dark:bg-slate-950/20" data-ay-name="{{ strtolower($ay->name) }}">
                        <td class="py-3 pr-4 font-black text-slate-800 dark:text-white cursor-pointer"
                            data-ay-col="name"
                            onclick="toggleTerms({{ $ay->id }})">
                            {{ $ay->name }}
                            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                Academic Year
                            </div>
                        </td>

                        <td class="py-3 pr-4 font-semibold" data-ay-col="start">
                            {{ \Carbon\Carbon::parse($ay->start_date)->format('Y-m-d') }}
                        </td>

                        <td class="py-3 pr-4 font-semibold" data-ay-col="end">
                            {{ \Carbon\Carbon::parse($ay->end_date)->format('Y-m-d') }}
                        </td>

                        <td class="py-3 pr-4" data-ay-col="status">
                            @php($status = $ay->computed_status)

                            @if($status === 'active')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 px-2 py-1 text-xs font-extrabold">
                                    Active
                                </span>
                            @elseif($status === 'upcoming')
                                <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-2 py-1 text-xs font-extrabold">
                                    Upcoming
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-200 text-slate-800 px-2 py-1 text-xs font-extrabold">
                                    Closed
                                </span>
                            @endif
                        </td>

                        <td class="py-3 pr-4" data-ay-col="actions">
                            <div class="flex items-center gap-2 whitespace-nowrap">

                                {{-- Edit AY --}}
                                <button type="button"
                                        class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-indigo-200 bg-indigo-50 text-indigo-800"
                                        onclick="openEditAYModal(
                                            {{ (int) $ay->id }},
                                            @js($ay->name),
                                            @js(\Carbon\Carbon::parse($ay->start_date)->format('Y-m-d')),
                                            @js(\Carbon\Carbon::parse($ay->end_date)->format('Y-m-d'))
                                        )">
                                    Edit
                                </button>

                                {{-- Delete AY --}}
                                <form method="POST" action="{{ route('school.settings.master-data.academic_year.destroy', $ay->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-red-200 bg-red-50 text-red-800">
                                        Delete
                                    </button>
                                </form>

                                {{-- Create Term --}}
                                <button type="button"
                                        class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-slate-200"
                                        onclick="openCreateTermModal(
                                            {{ (int) $ay->id }},
                                            @js($ay->name),
                                            @js(\Carbon\Carbon::parse($ay->start_date)->format('Y-m-d')),
                                            @js(\Carbon\Carbon::parse($ay->end_date)->format('Y-m-d'))
                                        )">
                                    Create Term
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Terms Rows --}}
                    @php($terms = ($termsByAcademicYearId[$ay->id] ?? collect()))

                    <tbody id="terms-{{ $ay->id }}" class="hidden">
                        @foreach($terms as $t)
                            <tr class="term-row cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/40"
                                data-term-id="{{ $t->id }}"
                                onclick="toggleSubjectOfferings({{ $t->id }})">
                                <td class="py-3 pr-4 pl-12" data-ay-col="name">
                                    <div class="font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                                        <span id="offerings-chevron-{{ $t->id }}" class="text-slate-400 text-xs">▸</span>
                                        {{ $t->name ?? $t->term }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        Term Type: <span class="font-semibold">{{ $t->term }}</span>
                                        @if(($t->subjectOfferings ?? collect())->count())
                                            · <span class="font-semibold">{{ $t->subjectOfferings->count() }} subject(s) offered</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="py-3 pr-4" data-ay-col="start">
                                    {{ \Carbon\Carbon::parse($t->start_date)->format('Y-m-d') }}
                                </td>

                                <td class="py-3 pr-4" data-ay-col="end">
                                    {{ \Carbon\Carbon::parse($t->end_date)->format('Y-m-d') }}
                                </td>

                                <td class="py-3 pr-4" data-ay-col="status">
                                    @php($status = $t->computed_status)

                                    @if($status === 'active')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 px-2 py-1 text-xs font-extrabold">
                                            Active
                                        </span>
                                    @elseif($status === 'upcoming')
                                        <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-2 py-1 text-xs font-extrabold">
                                            Upcoming
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-200 text-slate-800 px-2 py-1 text-xs font-extrabold">
                                            Closed
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 pr-4" data-ay-col="actions" onclick="event.stopPropagation()">
                                    <div class="flex flex-wrap items-center gap-2">

                                        

                                        {{-- Edit Term --}}
                                        <button type="button"
                                                class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-indigo-200 bg-indigo-50 text-indigo-800"
                                                onclick="openEditTermModal(
                                                    {{ (int) $ay->id }},
                                                    {{ (int) $t->id }},
                                                    @js($t->term),
                                                    @js(\Carbon\Carbon::parse($t->start_date)->format('Y-m-d')),
                                                    @js(\Carbon\Carbon::parse($t->end_date)->format('Y-m-d')),
                                                    @js($t->enrollment_type ?? 'regular'),
                                                    @js($t->title ?? '')
                                                )">
                                            Edit
                                        </button>

                                        {{-- Add Subjects (regular terms only) --}}
                                        @if(($t->enrollment_type ?? 'regular') === 'regular')
                                            <button type="button"
                                                    class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-emerald-200 bg-emerald-50 text-emerald-800"
                                                    onclick="openAddSubjectOfferingModal({{ (int) $t->id }}, @js($t->name ?? $t->term))">
                                                + Subjects
                                            </button>
                                        @endif

                                        {{-- Delete Term --}}
                                        <form method="POST" action="{{ route('school.settings.term.destroy', ['academicYearId' => $ay->id, 'id' => $t->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-red-200 bg-red-50 text-red-800">
                                                Delete
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>

                            {{-- Subject Offerings (hidden by default) - uses shareable x-table.table --}}
                            <tr id="offerings-{{ $t->id }}" class="hidden bg-slate-50/40 dark:bg-slate-950/30">
                                <td colspan="5" class="py-3 px-12" onclick="event.stopPropagation()">
                                    @php($offerings = $t->subjectOfferings ?? collect())
                                    @if($offerings->isEmpty())
                                        <div class="text-xs text-slate-500 italic">No subjects offered yet for this term.</div>
                                    @else
                                        <x-table.table
                                            :tableKey="'subject_offerings_' . $t->id"
                                            :columns="config('tables.subject_offerings.columns')"
                                            :data="$offerings"
                                            :actions="config('tables.table_actions.subject_offerings')"
                                            deleteRoute="subject-offerings.destroy"
                                            :perPage="10"
                                        />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500">
                            No academic years found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>