@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-extrabold">Validate Enrollment</h1>

        <form method="GET" class="flex items-center gap-2">
            <select name="status" onchange="this.form.submit()"
                    class="border rounded-lg px-2 py-1 text-sm">
                @foreach(['submitted','exam_passed','exam_failed','assessed','provisional','rejected','sent_billing','all'] as $opt)
                    <option value="{{ $opt }}" @selected($status === $opt)>{{ ucwords(str_replace('_',' ',$opt)) }}</option>
                @endforeach
            </select>
            <select name="term_id" onchange="this.form.submit()"
                    class="border rounded-lg px-2 py-1 text-sm">
                @foreach($terms as $t)
                    <option value="{{ $t->id }}" @selected($termId == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-3 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border bg-white overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Student #</th>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Program</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Units</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $e)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-mono text-xs">{{ optional($e->student)->student_number }}</td>
                        <td class="px-4 py-2">
                            {{ optional($e->student)->last_name }}, {{ optional($e->student)->first_name }}
                        </td>
                        <td class="px-4 py-2">{{ optional($e->program)->code ?? optional($e->program)->name }}</td>
                        <td class="px-4 py-2">{{ $e->enrollee_type }}</td>
                        <td class="px-4 py-2">{{ $e->total_units }}</td>
                        <td class="px-4 py-2">
                            @php
                                $colors = [
                                    'submitted'      => 'amber',
                                    'exam_passed'    => 'sky',
                                    'exam_failed'    => 'rose',
                                    'assessed'       => 'sky',
                                    'provisional'    => 'amber',
                                    'rejected'       => 'rose',
                                    'sent_billing'   => 'violet',
                                    'billed'         => 'yellow',
                                    'partially_paid' => 'yellow',
                                    'enrolled'       => 'emerald',
                                    'provisionally_enrolled' => 'emerald',
                                    'validated'      => 'emerald',
                                    'dropped'        => 'rose',
                                    'cancelled'      => 'rose',
                                    'completed'      => 'emerald',
                                ];
                                $labels = [
                                    'submitted'      => 'Submitted Application',
                                    'exam_passed'    => 'Passed Entrance Exam',
                                    'exam_failed'    => 'Failed Entrance Exam',
                                    'sent_billing'   => 'Sent Billing',
                                    'partially_paid' => 'Partially Paid',
                                    'provisionally_enrolled' => 'Provisionally Enrolled',
                                ];
                                $c = $colors[$e->status] ?? 'slate';
                                $label = $labels[$e->status] ?? ucfirst(str_replace('_', ' ', (string) $e->status));
                            @endphp
                            <span class="inline-flex items-center rounded-full bg-{{ $c }}-100 text-{{ $c }}-700 px-2 py-0.5 text-xs font-bold">
                                {{ $label }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('registrar.enrollments.show', $e) }}"
                               class="text-xs text-indigo-600 font-bold hover:underline">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No enrollments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $enrollments->links() }}</div>
</div>
@endsection
