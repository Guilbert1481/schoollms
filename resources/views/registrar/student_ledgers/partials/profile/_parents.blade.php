<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
        <i data-lucide="users" class="h-4 w-4 text-indigo-600"></i>
        <h3 class="text-sm font-bold text-slate-800">Parents / Guardians</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400">
                    <th class="pb-2 pr-4 font-semibold">Name</th>
                    <th class="pb-2 pr-4 font-semibold">Relationship</th>
                    <th class="pb-2 pr-4 font-semibold">Contact Number</th>
                    <th class="pb-2 pr-4 font-semibold">Email Address</th>
                    <th class="pb-2 font-semibold">Occupation</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($parents as $p)
                    <tr>
                        <td class="py-2 pr-4 font-semibold text-slate-800">{{ $p->name }}</td>
                        <td class="py-2 pr-4 text-slate-600">{{ $p->relationship }}</td>
                        <td class="py-2 pr-4 text-slate-600">{{ $p->contact }}</td>
                        <td class="py-2 pr-4 text-slate-600">{{ $p->email }}</td>
                        <td class="py-2 text-slate-600">{{ $p->occupation }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-center text-sm text-slate-400">No parents / guardians on record.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
