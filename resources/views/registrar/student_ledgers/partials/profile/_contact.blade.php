<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
        <i data-lucide="phone" class="h-4 w-4 text-indigo-600"></i>
        <h3 class="text-sm font-bold text-slate-800">Contact Information</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400">
                    <th class="pb-2 pr-4 font-semibold">Home Address</th>
                    <th class="pb-2 pr-4 font-semibold">Phone Number</th>
                    <th class="pb-2 pr-4 font-semibold">Alternate Number</th>
                    <th class="pb-2 font-semibold">Email Address</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-t border-slate-100">
                    <td class="py-2 pr-4 font-semibold text-slate-800">{{ $profile['home_address'] }}</td>
                    <td class="py-2 pr-4 text-slate-600">{{ $profile['phone'] }}</td>
                    <td class="py-2 pr-4 text-slate-600">{{ $profile['alternate'] }}</td>
                    <td class="py-2 text-slate-600">{{ $profile['email'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
