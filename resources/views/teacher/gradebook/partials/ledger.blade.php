{{--
    Right-drawer partial: one student's detailed grade ledger for a class (and, for
    basic ed, a grading period). Rendered by GradebookController@ledger and injected
    by RightDrawer.load(). Read-only; the "Add Record" button reuses the modal on
    the parent page (GbRecord.open()).
--}}
@php
    $fmtNum = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $sourceLabel = ['manual' => 'Manual', 'homework' => 'Homework', 'test' => 'Test'];
@endphp

<div class="px-5 pb-6 pt-5">
    <div class="mb-4 flex items-start justify-between gap-3 pr-8">
        <div>
            <h3 class="text-base font-semibold text-slate-800">{{ $studentName }}</h3>
            <p class="text-xs text-slate-400">{{ $studentNumber }}</p>
        </div>
        <button type="button" onclick="GbRecord.open()"
            class="gb-btn-blue inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold">
            <i data-lucide="plus" class="h-3.5 w-3.5"></i> Add Record
        </button>
    </div>

    @if (! $data)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-5 text-sm text-amber-800">
            No grading scheme is configured for this class's level, so there are no records to show yet.
        </div>
    @else
        @if ($data['track'] === 'basic')
            <div class="mb-4" style="max-width:240px;">
                <x-grading_period_basiced name="ledger_period" :selected="$data['period']"
                    onchange="GbLedger.open({{ $studentId }}, this.value)" />
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-3 py-2.5">Date</th>
                        @foreach ($data['components'] as $component)
                            <th class="px-3 py-2.5 text-center">{{ $component->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['lines'] as $line)
                        <tr class="border-b border-slate-100">
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <div class="font-medium text-slate-700">
                                    {{ $line['date'] ? \Illuminate\Support\Carbon::parse($line['date'])->format('M j, Y') : '—' }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $line['title'] }} · {{ $sourceLabel[$line['source']] ?? $line['source'] }}
                                </div>
                            </td>
                            @foreach ($data['components'] as $component)
                                <td class="px-3 py-2.5 text-center">
                                    @if ($line['component_id'] === $component->id)
                                        @if ($line['raw'] === null)
                                            <span class="text-slate-300" title="Not taken">—</span>
                                        @else
                                            <span class="font-medium text-slate-800">{{ $fmtNum($line['raw']) }}</span><span class="text-slate-400">/{{ $fmtNum($line['total']) }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-200">·</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $data['components']->count() + 1 }}" class="px-3 py-8 text-center text-sm text-slate-400">
                                No records yet. Use <span class="font-medium text-slate-500">Add Record</span> to enter scores.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($data['lines'] !== [])
                    <tfoot>
                        <tr class="border-t border-slate-200 bg-slate-50 text-[13px]">
                            <td class="px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Component %</td>
                            @foreach ($data['components'] as $component)
                                <td class="px-3 py-2.5 text-center font-semibold text-slate-700">
                                    @php($agg = $data['aggregates'][$component->id] ?? null)
                                    {{ $agg === null ? '—' : $fmtNum($agg) }}
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <p class="mt-3 text-[11px] text-slate-400">
            Each row is one graded item. Component % is the running score
            (Σ raw ÷ Σ total × 100) that feeds the gradebook.
        </p>
    @endif
</div>
