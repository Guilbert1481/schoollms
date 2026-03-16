@extends('layouts.app')
@section('content')
<div class="space-y-10">

    <!-- ============================ 7 KPI CARDS ============================ -->
    <section>
    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-7 gap-4">

        <div class="group relative rounded-2xl bg-gradient-to-b from-indigo-200 to-indigo-300 border border-indigo-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            <div class="flex items-center justify-between w-full mb-4 relative z-10">
                <div class="rounded-xl bg-indigo-600/20 p-2.5 border border-indigo-500/20 shadow-inner">
                    <svg class="h-6 w-6 text-indigo-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zM6 20V4h7v5h5v11H6z" opacity="0.4"/>
                        <path d="M13 9h5l-5-5v5zM9 11h6v2H9v-2zm0 4h6v2H9v-2z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black text-indigo-900 bg-white/40 px-2 py-0.5 rounded-md border border-white/30">▲ 4.3%</span>
            </div>
            <div class="text-3xl font-black text-black leading-none tracking-tight relative z-10">1,238</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-indigo-900/60 mt-3 relative z-10">Total Applications</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-teal-200 to-teal-300 border border-teal-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            <div class="flex items-center justify-between w-full mb-4 relative z-10">
                <div class="rounded-xl bg-teal-600/20 p-2.5 border border-teal-500/20 shadow-inner">
                    <svg class="h-6 w-6 text-teal-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2zm-2 10h-2.2c-.4 1.2-1.5 2-2.8 2s-2.4-.8-2.8-2H7V5h10v8z" opacity="0.4"/>
                        <path d="M7 13h2.2c.4 1.2 1.5 2 2.8 2s2.4-.8 2.8-2H17v6H7v-6z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black text-teal-900 bg-white/40 px-2 py-0.5 rounded-md border border-white/30">▲ 2.8%</span>
            </div>
            <div class="text-3xl font-black text-black leading-none tracking-tight relative z-10">803</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-teal-900/60 mt-3 relative z-10">Submitted</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-amber-200 to-amber-300 border border-amber-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            <div class="flex items-center justify-between w-full mb-4 relative z-10">
                <div class="rounded-xl bg-amber-600/20 p-2.5 border border-amber-500/20 shadow-inner">
                    <svg class="h-6 w-6 text-amber-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1z" opacity="0.4"/>
                        <path d="M15.5 14a2.5 2.5 0 10-4.33 1.76l-1.9 1.91 1.41 1.41 1.91-1.9A2.5 2.5 0 0015.5 14zm-2.5 1a1 1 0 110-2 1 1 0 010 2z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black text-amber-900 bg-white/40 px-2 py-0.5 rounded-md border border-white/30">▲ 5.1%</span>
            </div>
            <div class="text-3xl font-black text-black leading-none tracking-tight relative z-10">412</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-amber-900/60 mt-3 relative z-10">Under Review</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-sky-200 to-sky-300 border border-sky-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            <div class="flex items-center justify-between w-full mb-4 relative z-10">
                <div class="rounded-xl bg-sky-600/20 p-2.5 border border-sky-500/20 shadow-inner">
                    <svg class="h-6 w-6 text-sky-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" opacity="0.4"/>
                        <path d="M12 14c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black text-sky-900 bg-white/40 px-2 py-0.5 rounded-md border border-white/30">▲ 3.7%</span>
            </div>
            <div class="text-3xl font-black text-black leading-none tracking-tight relative z-10">125</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-sky-900/60 mt-3 relative z-10">For Interview</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-green-200 to-green-300 border border-green-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            <div class="flex items-center justify-between w-full mb-4 relative z-10">
                <div class="rounded-xl bg-green-600/20 p-2.5 border border-green-500/20 shadow-inner">
                    <svg class="h-6 w-6 text-green-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 1L9 4H5v4L2 11l3 3v4h4l3 3 3-3h4v-4l3-3-3-3V4h-4l-3-3z" opacity="0.4"/>
                        <path d="M10 17l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black text-green-900 bg-white/40 px-2 py-0.5 rounded-md border border-white/30">▲ 1.2%</span>
            </div>
            <div class="text-3xl font-black text-black leading-none tracking-tight relative z-10">82</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-green-900/60 mt-3 relative z-10">Approved</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-purple-200 to-purple-300 border border-purple-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            <div class="flex items-center justify-between w-full mb-4 relative z-10">
                <div class="rounded-xl bg-purple-600/20 p-2.5 border border-purple-500/20 shadow-inner">
                    <svg class="h-6 w-6 text-purple-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z" opacity="0.4"/>
                        <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black text-purple-900 bg-white/40 px-2 py-0.5 rounded-md border border-white/30">▲ 2.5%</span>
            </div>
            <div class="text-3xl font-black text-black leading-none tracking-tight relative z-10">67</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-purple-900/60 mt-3 relative z-10">Enrolled</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-rose-200 to-rose-300 border border-rose-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            <div class="flex items-center justify-between w-full mb-4 relative z-10">
                <div class="rounded-xl bg-rose-600/20 p-2.5 border border-rose-500/20 shadow-inner">
                    <svg class="h-6 w-6 text-rose-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" opacity="0.4"/>
                        <path d="M20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black text-rose-900 bg-white/40 px-2 py-0.5 rounded-md border border-white/30">▲ 6.0%</span>
            </div>
            <div class="text-3xl font-black text-black leading-none tracking-tight relative z-10">15</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-rose-900/60 mt-3 relative z-10">Adm. Test</div>
        </div>

    </div>
</section>

    <!-- ===================== MIDDLE ROW: TABLE/CHARTS (3 cards) ==================== -->
    <section class="grid grid-cols-12 gap-6">
        <!-- Left: Recent Applications (50%) -->
        <div class="col-span-6 bg-white rounded-2xl shadow-sm flex flex-col border border-gray-100">
            <div class="flex items-center justify-between px-6 pt-5 pb-2">
                <h2 class="text-lg font-bold text-slate-900">Recent Applications</h2>
                <a href="#" class="inline-flex items-center px-3 py-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded font-semibold text-xs transition">View All</a>
            </div>
            <div class="overflow-x-auto px-6 pb-5">
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        <tr class="hover:bg-indigo-50/60 transition group cursor-pointer">
                            <td class="py-3 font-medium text-slate-800">
                                Jane Delacruz <span class="text-xs text-gray-400 ml-2">BS Computer Science</span>
                            </td>
                            <td>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-600">Submitted</span>
                            </td>
                            <td class="text-right">
                                <a href="#" class="text-indigo-600 font-semibold hover:underline">View</a>
                            </td>
                        </tr>
                        <tr class="hover:bg-indigo-50/60 transition group cursor-pointer">
                            <td class="py-3 font-medium text-slate-800">
                                Martin Pineda <span class="text-xs text-gray-400 ml-2">AB Psychology</span>
                            </td>
                            <td>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-yellow-600">Under Review</span>
                            </td>
                            <td class="text-right">
                                <a href="#" class="text-indigo-600 font-semibold hover:underline">View</a>
                            </td>
                        </tr>
                        <tr class="hover:bg-indigo-50/60 transition group cursor-pointer">
                            <td class="py-3 font-medium text-slate-800">
                                Audrey Kim <span class="text-xs text-gray-400 ml-2">BSN Nursing</span>
                            </td>
                            <td>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-cyan-100 text-cyan-700">For Interview</span>
                            </td>
                            <td class="text-right">
                                <a href="#" class="text-indigo-600 font-semibold hover:underline">View</a>
                            </td>
                        </tr>
                        <tr class="hover:bg-indigo-50/60 transition group cursor-pointer">
                            <td class="py-3 font-medium text-slate-800">
                                Alicia Sy <span class="text-xs text-gray-400 ml-2">BS Accounting</span>
                            </td>
                            <td>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-600">Approved</span>
                            </td>
                            <td class="text-right">
                                <a href="#" class="text-indigo-600 font-semibold hover:underline">View</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Middle: Application Pipeline (25%) -->
        <div class="col-span-3 bg-white rounded-2xl shadow-sm flex flex-col border border-gray-100">
            <div class="flex items-center justify-between px-6 pt-5 pb-2">
                <h2 class="text-lg font-bold text-slate-900">Application Status Pipeline</h2>
                <a href="#" class="inline-flex items-center px-3 py-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded font-semibold text-xs">View All</a>
            </div>
            <div class="flex flex-col items-center justify-center p-6">
                <!-- Donut Chart Placeholder -->
                <div class="w-36 h-36 bg-slate-100 rounded-full flex items-center justify-center mb-4 relative" style="box-shadow:0 2px 8px #0001,0 1.5px 0px #fff2 inset">
                    <span class="font-semibold text-indigo-600 text-xl">62%<br><span class="text-xs text-slate-400 font-normal block">Completion</span></span>
                </div>
                <ul class="w-full space-y-2">
                    <li class="flex items-center">
                        <span class="inline-block h-3 w-3 rounded-full bg-indigo-500 mr-2"></span>
                        <span class="text-xs font-semibold text-slate-700 flex-1">Submitted</span>
                        <span class="text-xs font-medium text-slate-600">42%</span>
                    </li>
                    <li class="flex items-center">
                        <span class="inline-block h-3 w-3 rounded-full bg-amber-400 mr-2"></span>
                        <span class="text-xs font-semibold text-slate-700 flex-1">Under Review</span>
                        <span class="text-xs font-medium text-slate-600">30%</span>
                    </li>
                    <li class="flex items-center">
                        <span class="inline-block h-3 w-3 rounded-full bg-green-400 mr-2"></span>
                        <span class="text-xs font-semibold text-slate-700 flex-1">Approved</span>
                        <span class="text-xs font-medium text-slate-600">14%</span>
                    </li>
                    <li class="flex items-center">
                        <span class="inline-block h-3 w-3 rounded-full bg-cyan-400 mr-2"></span>
                        <span class="text-xs font-semibold text-slate-700 flex-1">Enrolled</span>
                        <span class="text-xs font-medium text-slate-600">7%</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right: Applications by Program (25%) -->
        <div class="col-span-3 bg-white rounded-2xl shadow-sm flex flex-col border border-gray-100">
            <div class="flex items-center justify-between px-6 pt-5 pb-2">
                <h2 class="text-lg font-bold text-slate-900">Applications by Program</h2>
                <a href="#" class="inline-flex items-center px-3 py-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded font-semibold text-xs">View All</a>
            </div>
            <div class="flex flex-col justify-center px-6 py-6 gap-4">
                <!-- Bar Chart Placeholder 1 -->
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-semibold text-slate-700 flex-1">BS Computer Science</span>
                        <span class="text-xs font-medium text-slate-500">41%</span>
                    </div>
                    <div class="rounded-full h-3 bg-gradient-to-r from-indigo-400 to-indigo-700 relative overflow-hidden">
                        <div class="absolute left-0 top-0 h-3 bg-white/15 rounded-full" style="width: 41%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-semibold text-slate-700 flex-1">AB Psychology</span>
                        <span class="text-xs font-medium text-slate-500">24%</span>
                    </div>
                    <div class="rounded-full h-3 bg-gradient-to-r from-pink-400 to-pink-700 relative overflow-hidden">
                        <div class="absolute left-0 top-0 h-3 bg-white/15 rounded-full" style="width: 24%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-semibold text-slate-700 flex-1">BS Nursing</span>
                        <span class="text-xs font-medium text-slate-500">19%</span>
                    </div>
                    <div class="rounded-full h-3 bg-gradient-to-r from-cyan-400 to-blue-700 relative overflow-hidden">
                        <div class="absolute left-0 top-0 h-3 bg-white/15 rounded-full" style="width: 19%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-semibold text-slate-700 flex-1">BS Accountancy</span>
                        <span class="text-xs font-medium text-slate-500">16%</span>
                    </div>
                    <div class="rounded-full h-3 bg-gradient-to-r from-amber-400 to-orange-500 relative overflow-hidden">
                        <div class="absolute left-0 top-0 h-3 bg-white/15 rounded-full" style="width: 16%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =================== BOTTOM ROW: ANNOUNCEMENTS/DEADLINES (2 cards) =================== -->
    <section class="grid grid-cols-12 gap-6">
        <!-- Left: Announcements (75%) -->
        <div class="col-span-9 bg-gradient-to-tr from-slate-50 via-white/80 to-slate-100/90 shadow-sm rounded-2xl flex flex-col border border-gray-100">
            <div class="flex items-center justify-between px-6 pt-5 pb-2">
                <h2 class="text-lg font-bold text-slate-900">Announcements</h2>
                <a href="#" class="inline-flex items-center px-3 py-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded font-semibold text-xs">View All</a>
            </div>
            <div class="divide-y divide-gray-100 px-6 pb-6">
                <div class="py-3">
                    <div class="font-semibold text-sm text-slate-800">Orientation for New Students</div>
                    <div class="text-xs text-slate-500">Join us on March 2 for the campus tour and orientation program. Attendance mandatory.</div>
                </div>
                <div class="py-3">
                    <div class="font-semibold text-sm text-slate-800">Application Portal Maintenance</div>
                    <div class="text-xs text-slate-500">The portal will be unavailable Feb 20, 10pm-2am, for scheduled updates.</div>
                </div>
                <div class="py-3">
                    <div class="font-semibold text-sm text-slate-800">Scholarship Results</div>
                    <div class="text-xs text-slate-500">Results will be released on March 5. Check your applicant dashboard for details.</div>
                </div>
            </div>
        </div>
        <!-- Right: Deadlines (25%) -->
        <div class="col-span-3 bg-gradient-to-tr from-slate-50 via-white/80 to-slate-100/90 shadow-sm rounded-2xl flex flex-col border border-gray-100">
            <div class="flex items-center justify-between px-6 pt-5 pb-2">
                <h2 class="text-lg font-bold text-slate-900">Deadlines</h2>
                <a href="#" class="inline-flex items-center px-3 py-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded font-semibold text-xs">View All</a>
            </div>
            <div class="px-6 pb-6 divide-y divide-gray-100">
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-sm text-slate-800">Regular Application</div>
                        <div class="text-xs text-slate-400">Feb 28, 2026</div>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-50 text-red-500 font-semibold">14 Days Left</span>
                </div>
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-sm text-slate-800">Entrance Exam</div>
                        <div class="text-xs text-slate-400">Mar 10, 2026</div>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-50 text-yellow-500 font-semibold">24 Days Left</span>
                </div>
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-sm text-slate-800">Document Submission</div>
                        <div class="text-xs text-slate-400">Mar 15, 2026</div>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 font-semibold">29 Days Left</span>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection