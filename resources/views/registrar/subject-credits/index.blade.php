@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-xl font-extrabold mb-1">Subject Credit Evaluations</h1>
    <p class="text-sm text-slate-500 mb-4">For transferees, irregulars, shifters, and returnees.</p>

    @if(session('success'))
        <div class="mb-3 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-3 gap-4">
        {{-- LEFT: Create form --}}
        <div class="col-span-1 rounded-2xl border bg-white p-5">
            <h2 class="font-extrabold mb-3">New Evaluation Request</h2>

            <form method="POST" action="{{ route('registrar.subject-credits.store') }}" class="space-y-3 text-sm">
                @csrf

                <div>
                    <label class="text-xs font-bold">Student</label>
                    <select name="student_id" required class="w-full border rounded-lg px-2 py-2">
                        <option value="">—</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}">
                                [{{ $s->student_number }}] {{ $s->last_name }}, {{ $s->first_name }}
                                @if($s->status) — {{ $s->status }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold">Curriculum Subject</label>
                    <select name="subject_id" required class="w-full border rounded-lg px-2 py-2">
                        <option value="">—</option>
                        @foreach($subjects as $sub)
                            <option value="{{ $sub->id }}" title="{{ $sub->title }}">{{ $sub->code }} ({{ $sub->units }}u)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold">Reason</label>
                    <select name="reason" required class="w-full border rounded-lg px-2 py-2">
                        <option value="transferee">Transferee</option>
                        <option value="irregular">Irregular</option>
                        <option value="shifter">Shifter</option>
                        <option value="returnee">Returnee</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-bold">Source Code</label>
                        <input type="text" name="source_subject_code" class="w-full border rounded-lg px-2 py-2">
                    </div>
                    <div>
                        <label class="text-xs font-bold">Source Units</label>
                        <input type="number" step="0.5" name="source_units" class="w-full border rounded-lg px-2 py-2">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold">Source Subject Title</label>
                    <input type="text" name="source_subject_title" class="w-full border rounded-lg px-2 py-2">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-bold">Source School</label>
                        <input type="text" name="source_school" class="w-full border rounded-lg px-2 py-2">
                    </div>
                    <div>
                        <label class="text-xs font-bold">Source Grade</label>
                        <input type="text" name="source_grade" class="w-full border rounded-lg px-2 py-2">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold">Remarks</label>
                    <textarea name="remarks" rows="2" class="w-full border rounded-lg px-2 py-2"></textarea>
                </div>

                <button class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold">
                    Create Request
                </button>
            </form>
        </div>

        {{-- RIGHT: Queue --}}
        <div class="col-span-2 rounded-2xl border bg-white">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <form method="GET" class="flex items-center gap-2">
                    <select name="status" onchange="this.form.submit()" class="border rounded-lg px-2 py-1 text-sm">
                        @foreach(['pending','credited','rejected','conditional','all'] as $opt)
                            <option value="{{ $opt }}" @selected($status === $opt)>{{ ucfirst($opt) }}</option>
                        @endforeach
                    </select>
                    <select name="reason" onchange="this.form.submit()" class="border rounded-lg px-2 py-1 text-sm">
                        @foreach(['all','transferee','irregular','shifter','returnee','other'] as $opt)
                            <option value="{{ $opt }}" @selected($reason === $opt)>{{ ucfirst($opt) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Student</th>
                        <th class="px-3 py-2">Subject</th>
                        <th class="px-3 py-2">Source</th>
                        <th class="px-3 py-2">Reason</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($evaluations as $ev)
                        <tr class="border-t align-top">
                            <td class="px-3 py-2 text-xs">
                                [{{ optional($ev->student)->student_number }}]<br>
                                {{ optional($ev->student)->last_name }}, {{ optional($ev->student)->first_name }}
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <span class="font-bold" title="{{ optional($ev->subject)->title }}">{{ optional($ev->subject)->code }}</span>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                {{ $ev->source_subject_code }} {{ $ev->source_subject_title ? '— ' . $ev->source_subject_title : '' }}
                                @if($ev->source_school)<br><span class="text-slate-500">{{ $ev->source_school }}</span>@endif
                            </td>
                            <td class="px-3 py-2 text-xs">{{ ucfirst($ev->reason) }}</td>
                            <td class="px-3 py-2 text-xs">
                                @php
                                    $c = ['pending'=>'amber','credited'=>'emerald','rejected'=>'rose','conditional'=>'sky'][$ev->status] ?? 'slate';
                                @endphp
                                <span class="inline-flex items-center rounded-full bg-{{ $c }}-100 text-{{ $c }}-700 px-2 py-0.5 font-bold">
                                    {{ ucfirst($ev->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                @if($ev->status === 'pending')
                                    <details>
                                        <summary class="text-xs text-indigo-600 font-bold cursor-pointer">Decide</summary>
                                        <form method="POST" action="{{ route('registrar.subject-credits.decide', $ev) }}"
                                              class="mt-2 space-y-2 text-left">
                                            @csrf
                                            <select name="status" required class="w-full border rounded px-2 py-1 text-xs">
                                                <option value="credited">Credited</option>
                                                <option value="conditional">Conditional</option>
                                                <option value="rejected">Rejected</option>
                                            </select>
                                            <input type="number" step="0.5" name="credited_units"
                                                   placeholder="Credited units"
                                                   class="w-full border rounded px-2 py-1 text-xs">
                                            <textarea name="remarks" rows="2" placeholder="Remarks"
                                                      class="w-full border rounded px-2 py-1 text-xs"></textarea>
                                            <button class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-bold">Save</button>
                                        </form>
                                    </details>
                                @else
                                    @if($ev->credited_units)<div class="text-xs">{{ $ev->credited_units }}u credited</div>@endif
                                    @if($ev->remarks)<div class="text-xs text-slate-500">{{ $ev->remarks }}</div>@endif
                                @endif

                                <form method="POST" action="{{ route('registrar.subject-credits.destroy', $ev) }}"
                                      class="inline mt-1"
                                      onsubmit="return confirm('Delete this evaluation?');">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-rose-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No evaluations.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="p-3">{{ $evaluations->links() }}</div>
        </div>
    </div>
</div>
@endsection
