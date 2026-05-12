@extends('layouts.app')

@section('content')
<div class="w-full space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Trainors</h1>
        <p class="text-sm text-slate-500">Trainors in this school.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        @if($trainors->isEmpty())
            <div class="p-12 text-center text-slate-500">No trainors yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Joined</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($trainors as $t)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $t->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $t->email }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($t->created_at)->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100">{{ $trainors->links() }}</div>
        @endif
    </div>
</div>
@endsection
