@extends('layouts.app')
@section('content')
<div class="space-y-10">

    <section>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6">

        <div class="group relative rounded-2xl bg-gradient-to-b from-indigo-200 to-indigo-300 border border-indigo-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            
            <div class="flex items-start justify-between w-full relative z-10">
                <div class="rounded-xl bg-indigo-600/20 p-2.5 border border-indigo-500/20 shadow-inner">
                    <svg class="h-5 w-5 text-indigo-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 6h16v2H4zm2 11h12l-6-9z" opacity="0.4"/><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-900 bg-white/30 px-2 py-1 rounded-md border border-white/20">▲ 3.2%</span>
            </div>
            
            <div class="text-3xl font-black text-black mt-4 leading-none tracking-tight relative z-10">1,238</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-indigo-900/60 mt-3 relative z-10">Total Programs</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-teal-200 to-teal-300 border border-teal-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            
            <div class="flex items-start justify-between w-full relative z-10">
                <div class="rounded-xl bg-teal-600/20 p-2.5 border border-teal-500/20 shadow-inner">
                    <svg class="h-5 w-5 text-teal-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" opacity="0.4"/><path d="M12 14c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-teal-900 bg-white/30 px-2 py-1 rounded-md border border-white/20">▲ 1.8%</span>
            </div>
            
            <div class="text-3xl font-black text-black mt-4 leading-none tracking-tight relative z-10">803</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-teal-900/60 mt-3 relative z-10">Active Faculty</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-orange-200 to-orange-300 border border-orange-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            
            <div class="flex items-start justify-between w-full relative z-10">
                <div class="rounded-xl bg-orange-600/20 p-2.5 border border-orange-500/20 shadow-inner">
                    <svg class="h-5 w-5 text-orange-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z" opacity="0.4"/><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-orange-900 bg-white/30 px-2 py-1 rounded-md border border-white/20">▲ 5.1%</span>
            </div>
            
            <div class="text-3xl font-black text-black mt-4 leading-none tracking-tight relative z-10">412</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-orange-900/60 mt-3 relative z-10">Enrolled Students</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-sky-200 to-sky-300 border border-sky-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            
            <div class="flex items-start justify-between w-full relative z-10">
                <div class="rounded-xl bg-sky-600/20 p-2.5 border border-sky-500/20 shadow-inner">
                    <svg class="h-5 w-5 text-sky-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71L12 2z" opacity="0.4"/><path d="M12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.37L12 6.1l1.7 4.04 4.38.37-3.32 2.88 1 4.28L12 15.4z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-sky-900 bg-white/30 px-2 py-1 rounded-md border border-white/20">▲ 3.7%</span>
            </div>
            
            <div class="text-3xl font-black text-black mt-4 leading-none tracking-tight relative z-10">125</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-sky-900/60 mt-3 relative z-10">Accredited Programs</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-purple-200 to-purple-300 border border-purple-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            
            <div class="flex items-start justify-between w-full relative z-10">
                <div class="rounded-xl bg-purple-600/20 p-2.5 border border-purple-500/20 shadow-inner">
                    <svg class="h-5 w-5 text-purple-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21 3H3v18h18V3zM6 17H3v-3h3v3zm0-4H3v-3h3v3zm0-4H3V6h3v3zm15 8h-3v-3h3v3zm0-4h-3v-3h3v3zm0-4h-3V6h3v3z" opacity="0.4"/><path d="M12 7l-4.5 2h9L12 7zm0 10l4.5-2h-9l4.5 2z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-purple-900 bg-white/30 px-2 py-1 rounded-md border border-white/20">▲ 2.5%</span>
            </div>
            
            <div class="text-3xl font-black text-black mt-4 leading-none tracking-tight relative z-10">67</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-purple-900/60 mt-3 relative z-10">Policy Updates</div>
        </div>

        <div class="group relative rounded-2xl bg-gradient-to-b from-rose-200 to-rose-300 border border-rose-400/30 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-5 min-h-[140px] overflow-hidden">
            <div class="absolute inset-0 rounded-2xl border-t border-white/40 pointer-events-none"></div>
            
            <div class="flex items-start justify-between w-full relative z-10">
                <div class="rounded-xl bg-rose-600/20 p-2.5 border border-rose-500/20 shadow-inner">
                    <svg class="h-5 w-5 text-rose-900" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z" opacity="0.4"/><path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-rose-900 bg-white/30 px-2 py-1 rounded-md border border-white/20">▲ 6.0%</span>
            </div>
            
            <div class="text-3xl font-black text-black mt-4 leading-none tracking-tight relative z-10">15</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-rose-900/60 mt-3 relative z-10">Pending Requests</div>
        </div>

    </div>
</section>

<!-- ===================== 2ND ROW ===================== -->
<section class="grid grid-cols-12 gap-6">

    <!-- LEFT: Pending Academic Approvals -->
    <div class="col-span-7 bg-white rounded-2xl shadow-sm border border-gray-100">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 pt-5 pb-3">
            <h2 class="text-lg font-bold text-slate-900">
                Pending Academic Approvals
            </h2>
            <a href="#"
               class="inline-flex items-center px-3 py-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded font-semibold text-xs">
                View All
            </a>
        </div>

        <!-- Table -->
        <div class="px-6 pb-6 overflow-x-auto">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100">

                    <!-- Row 1 -->
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-4">
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                <span class="font-medium text-slate-800">
                                    Policy Revision Update
                                </span>
                            </div>
                        </td>

                        <!-- CENTER COLUMN -->
                        <td class="py-4 text-center">
                            <div class="text-left inline-block w-28 text-slate-500">
                                Policy
                            </div>
                        </td>

                        <td class="py-4 text-right">
                            <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-semibold text-slate-700">
                                For Review →
                            </button>
                        </td>
                    </tr>

                    <!-- Row 2 -->
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-4">
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                <span class="font-medium text-slate-800">
                                    Graduation Petition
                                </span>
                            </div>
                        </td>

                        <td class="py-4 text-center">
                            <div class="text-left inline-block w-28 text-slate-500">
                                Graduation
                            </div>
                        </td>

                        <td class="py-4 text-right">
                            <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-semibold text-slate-700">
                                Pending →
                            </button>
                        </td>
                    </tr>

                    <!-- Row 3 -->
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-4">
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                <span class="font-medium text-slate-800">
                                    Syllabus Adjustment
                                </span>
                            </div>
                        </td>

                        <td class="py-4 text-center">
                            <div class="text-left inline-block w-28 text-slate-500">
                                Curriculum
                            </div>
                        </td>

                        <td class="py-4 text-right">
                            <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-semibold text-slate-700">
                                Approve →
                            </button>
                        </td>
                    </tr>

                    <!-- Row 4 -->
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-4">
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                <span class="font-medium text-slate-800">
                                    Honors Recognition Request
                                </span>
                            </div>
                        </td>

                        <td class="py-4 text-center">
                            <div class="text-left inline-block w-28 text-slate-500">
                                Graduation
                            </div>
                        </td>

                        <td class="py-4 text-right">
                            <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-semibold text-slate-700">
                                For Review →
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>

    <div class="col-span-5 bg-white rounded-2xl shadow-sm border border-gray-100">

    <!-- Header -->
    <div class="flex items-center justify-between px-6 pt-5 pb-3">
        <h2 class="text-lg font-bold text-slate-900">
            Academic Performance
        </h2>

        <div class="relative">
            <select class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option>Last 4 Year</option>
                <option>Last 5 Year</option>
                <option>Last 10 Year</option>
            </select>
        </div>
    </div>

    <!-- Chart -->
    <div class="px-6 pb-6 space-y-4 text-sm">
        <div class="relative h-64">
            <canvas id="academicPerformanceChart"></canvas>
        </div>

</div>

</section>



    <!-- ===================== BOTTOM ROW ==================== -->
    <section class="grid grid-cols-12 gap-6">


        <!-- ================= Announcements (Span 6) ================= -->
    <div class="col-span-6 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-900">
                Announcements
            </h2>

            <button class="text-sm font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition">
                View All
            </button>
        </div>

        <!-- Content -->
        <div class="divide-y divide-slate-100">

            <!-- Item -->
            <div class="p-6 hover:bg-slate-50 transition cursor-pointer">
                <div class="flex items-start gap-4">

                    <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="megaphone" class="w-5 h-5"></i>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-slate-800">
                                Curriculum Review Committee Meeting
                            </h3>
                            <span class="text-xs text-slate-400">Feb 20, 2026</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">
                            All department heads must attend the annual curriculum alignment session.
                        </p>
                    </div>

                </div>
            </div>

            <!-- Item -->
            <div class="p-6 hover:bg-slate-50 transition cursor-pointer">
                <div class="flex items-start gap-4">

                    <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-slate-800">
                                Accreditation Preparation Week
                            </h3>
                            <span class="text-xs text-slate-400">Mar 5, 2026</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">
                            All academic documents must be finalized and uploaded before audit review.
                        </p>
                    </div>

                </div>
            </div>

            <!-- Item -->
            <div class="p-6 hover:bg-slate-50 transition cursor-pointer">
                <div class="flex items-start gap-4">

                    <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-slate-800">
                                Faculty Performance Evaluation
                            </h3>
                            <span class="text-xs text-slate-400">Mar 15, 2026</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">
                            Mid-year evaluation forms are now available in the faculty portal.
                        </p>
                    </div>

                </div>
            </div>

        </div>

    </div>


    <!-- ================= Deadlines (Span 3) ================= -->
    <div class="col-span-3 bg-white rounded-2xl shadow-sm border border-slate-200">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-900">
                Deadlines
            </h2>
            <a href="#"
               class="inline-flex items-center px-3 py-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded font-semibold text-xs">
                View All
            </a>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-5">

            <!-- Deadline -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-800 text-sm">
                        Syllabus Submission
                    </p>
                    <p class="text-xs text-slate-400">
                        Feb 28, 2026
                    </p>
                </div>

                <span class="text-xs font-semibold px-2 py-1 rounded-full bg-red-50 text-red-600">
                    14 Days
                </span>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-800 text-sm">
                        Graduation Clearance
                    </p>
                    <p class="text-xs text-slate-400">
                        Mar 10, 2026
                    </p>
                </div>

                <span class="text-xs font-semibold px-2 py-1 rounded-full bg-amber-50 text-amber-600">
                    24 Days
                </span>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-800 text-sm">
                        Accreditation Report
                    </p>
                    <p class="text-xs text-slate-400">
                        Mar 15, 2026
                    </p>
                </div>

                <span class="text-xs font-semibold px-2 py-1 rounded-full bg-indigo-50 text-indigo-600">
                    29 Days
                </span>
            </div>

        </div>
    </div>



        <!-- RIGHT: Accreditation Status -->
    <div class="col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 pt-5 pb-3">
            <h2 class="text-lg font-bold text-slate-900">
                Accreditation Status
            </h2>
            <a href="#"
               class="inline-flex items-center px-3 py-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded font-semibold text-xs">
                View All
            </a>
        </div>

        <!-- Content -->
        <div class="px-6 pb-6">
            
                <!-- Legend -->
                <!-- Chart -->
    <div class="flex flex-col items-center">

        <div class="w-40 h-40">
            <canvas id="accreditationChart"></canvas>
        </div>

        <!-- Legend Below -->
        <div class="mt-6 space-y-3 w-full max-w-xs">

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                    <span class="text-sm text-slate-700">Initial Review</span>
                </div>
                <span class="text-sm font-semibold text-slate-800">41%</span>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                    <span class="text-sm text-slate-700">Compliance Phase</span>
                </div>
                <span class="text-sm font-semibold text-slate-800">30%</span>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span class="text-sm text-slate-700">Accredited</span>
                </div>
                <span class="text-sm font-semibold text-slate-800">19%</span>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                    <span class="text-sm text-slate-700">Not Accredited</span>
                </div>
                <span class="text-sm font-semibold text-slate-800">10%</span>
            </div>

        </div>
            
        </div>
    </div>
        

    </section>

</div>

@endsection

<script>
document.addEventListener("DOMContentLoaded", function () {

    const centerTextPlugin = {
        id: 'centerText',
        beforeDraw(chart) {
            const { width, height, ctx } = chart;
            ctx.restore();

            const dataset = chart.data.datasets[0].data;
            const total = dataset.reduce((a, b) => a + b, 0);
            const active = dataset[0] + dataset[1] + dataset[2];
            const percent = Math.round((active / total) * 100);

            // Smaller Percentage
            ctx.font = "bold 20px Inter, sans-serif";
            ctx.fillStyle = "#1e293b";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(percent + "%", width / 2, height / 2 - 6);

            // Smaller Subtitle
            ctx.font = "11px Inter, sans-serif";
            ctx.fillStyle = "#64748b";
            ctx.fillText("In Progress", width / 2, height / 2 + 12);

            ctx.save();
        }
    };

    new Chart(document.getElementById('accreditationChart'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [41, 30, 19, 10],
                backgroundColor: [
                    '#2563eb',
                    '#f97316',
                    '#10b981',
                    '#94a3b8'
                ],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '72%',
            plugins: {
                legend: { display: false }
            }
        },
        plugins: [centerTextPlugin]
    });

});
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const ctx = document.getElementById('academicPerformanceChart').getContext('2d');

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(16,185,129,0.35)');
    gradient.addColorStop(1, 'rgba(16,185,129,0.05)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Even', 'Odd', 'Even', 'Odd', 'Even', 'Odd', 'Even', 'Odd'],
            datasets: [{
                data: [33, 45, 40, 48, 60, 70, 66, 62],
                borderColor: '#10b981',
                backgroundColor: gradient,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#10b981',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.raw + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    },
                    grid: {
                        color: '#e5e7eb'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

});
</script>

