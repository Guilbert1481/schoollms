@extends('layouts.app')

@section('content')
<div class="w-full space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Training Courses</h1>
        <p class="text-sm text-slate-500">All training courses offered by this school.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        @if($courses->isEmpty())
            <div class="p-12 text-center text-slate-500">No training courses yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($courses as $c)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $c->course_name ?? $c->name }}</td>
                                <td class="px-4 py-3">{{ $c->status ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('training.program_head.courses.show', $c->id) }}"
                                       class="rounded-lg border border-slate-300 px-3 py-1 text-xs text-slate-700 hover:bg-slate-50">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100">{{ $courses->links() }}</div>
        @endif
    </div>
</div>
@endsection
