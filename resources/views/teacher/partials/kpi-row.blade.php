<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div class="p-2 bg-purple-50 w-fit rounded-lg text-purple-600 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Classes</p>
        <h4 class="text-2xl font-black text-slate-800">05</h4>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div class="p-2 bg-blue-50 w-fit rounded-lg text-blue-600 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M12 14l9-5-9-5-9 5 9 5z" /><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
            </svg>
        </div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Avg. Grade</p>
        <h4 class="text-2xl font-black text-slate-800">82.4%</h4>
    </div>
    <div @click="openAttendance = true" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:border-emerald-400 cursor-pointer transition-all group">
        <div class="flex justify-between items-start mb-3">
            <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600 group-hover:bg-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-600">-2%</span>
        </div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Attendance Today</p>
        <h4 class="text-2xl font-black text-slate-800">94%</h4>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div class="p-2 bg-amber-50 w-fit rounded-lg text-amber-600 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
        </div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Grading</p>
        <h4 class="text-2xl font-black text-slate-800">28</h4>
    </div>
    <div @click="openAtRisk = true" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:border-rose-400 cursor-pointer transition-all group">
        <div class="flex justify-between items-start mb-3">
            <div class="p-2 bg-rose-50 rounded-lg text-rose-600 group-hover:bg-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">Action</span>
        </div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">At-Risk Students</p>
        <h4 class="text-2xl font-black text-slate-800">06</h4>
    </div>
</div>