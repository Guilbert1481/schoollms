@extends('layouts.app')

@section('content')


{{-- resources/views/admin/dashboard.blade.php --}}
<div class="w-full p-0 md:p-6 bg-slate-50 min-h-screen flex flex-col gap-6"
     x-data="{ 
        openAttendance: false, 
        openAtRisk: false, 
        openGradeModal: false 
     }">

    <!-- ========================= -->
    <!-- ROW 1: KPI CARDS -->
    <!-- ========================= -->
    <div class="w-full grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <!-- Total Classes -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <div class="p-2 bg-purple-50 w-fit rounded-lg text-purple-600 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Classes</p>
            <h4 class="text-2xl font-black text-slate-800">05</h4>
        </div>

        <!-- Avg Grade -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <div class="p-2 bg-blue-50 w-fit rounded-lg text-blue-600 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
            </div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Avg. Grade</p>
            <h4 class="text-2xl font-black text-slate-800">82.4%</h4>
        </div>

        <!-- Attendance -->
        <div @click="openAttendance = true"
             class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:border-emerald-400 cursor-pointer transition-all group">
            <div class="flex justify-between items-start mb-3">
                <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600 group-hover:bg-emerald-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-600">-2%</span>
            </div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Attendance Today</p>
            <h4 class="text-2xl font-black text-slate-800">94%</h4>
        </div>

        <!-- Pending Grading -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <div class="p-2 bg-amber-50 w-fit rounded-lg text-amber-600 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Grading</p>
            <h4 class="text-2xl font-black text-slate-800">28</h4>
        </div>

        <!-- At Risk -->
        <div @click="openAtRisk = true"
             class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:border-rose-400 cursor-pointer transition-all group">
            <div class="flex justify-between items-start mb-3">
                <div class="p-2 bg-rose-50 rounded-lg text-rose-600 group-hover:bg-rose-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">Action</span>
            </div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">At-Risk Students</p>
            <h4 class="text-2xl font-black text-slate-800">06</h4>
        </div>

    </div>

    <!-- ========================= -->
    <!-- ROW 2: CHARTS -->
    <!-- ========================= -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    <!-- Grade Distribution -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 cursor-pointer"
        @click="openGradeModal = true">
        <h3 class="text-md font-bold text-slate-700 mb-4">Grade Distribution</h3>
        <div class="h-[250px]">
            <canvas id="gradeChart"></canvas>
        </div>
    </div>

    <!-- Attendance Performance Trend -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h3 class="text-md font-bold text-slate-700 mb-4">Attendance Performance Trend</h3>
        <div class="h-[250px]">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>

    <!-- Subject Performance -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h3 class="text-md font-bold text-slate-700 mb-4">Subject Performance</h3>
        <div class="h-[250px]">
            <canvas id="subjectChart"></canvas>
        </div>
    </div>

</div>

    <!-- ========================= -->
    <!-- ROW 3: ANNOUNCEMENTS + DEADLINES -->
    <!-- ========================= -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <!-- Announcements -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-md font-bold text-slate-700">School Announcements</h3>
                <button class="text-xs font-bold text-blue-600 hover:underline">View All</button>
            </div>

            <div class="divide-y divide-slate-50">
                <div class="p-4 hover:bg-slate-50 transition">
                    <p class="text-sm font-bold text-slate-800">Faculty General Assembly</p>
                    <p class="text-xs text-slate-500">Friday at 3:00 PM | Conference Hall</p>
                </div>

                <div class="p-4 hover:bg-slate-50 transition">
                    <p class="text-sm font-bold text-slate-800">Final Exam Schedule Posted</p>
                    <p class="text-xs text-slate-500">Check the registrar portal for your room assignments.</p>
                </div>
            </div>
        </div>

        <!-- Deadlines -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-md font-bold text-slate-700">Upcoming Deadlines</h3>
                <button class="text-xs font-bold text-blue-600 hover:underline">View All</button>
            </div>

            <div class="divide-y divide-slate-50">
                <div class="p-4 flex justify-between items-center hover:bg-slate-50 transition">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Midterm Grade Encoding</p>
                    </div>
                    <span class="px-2 py-1 bg-rose-50 text-rose-600 text-[10px] font-black rounded uppercase">
                        Due in 2 days
                    </span>
                </div>

                <div class="p-4 flex justify-between items-center hover:bg-slate-50 transition">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Syllabus Submission</p>
                    </div>
                    <span class="px-2 py-1 bg-amber-50 text-amber-600 text-[10px] font-black rounded uppercase">
                        Due in 5 days
                    </span>
                </div>
            </div>
        </div>

    </div>

    @include('teacher.partials.modals')

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {

    /* ============================
       GRADE DISTRIBUTION CHART
       (Clear meaning: A=90-100, B=80-89, etc.)
    ============================ */
    new Chart(document.getElementById('gradeChart'), {
        type: 'bar',
        data: {
            labels: ['A (90-100)', 'B (80-89)', 'C (70-79)', 'D (60-69)', 'F (<60)'],
            datasets: [{
                label: 'Number of Students',
                data: [10, 18, 22, 8, 3],
                backgroundColor: [
                    '#16a34a', // A
                    '#3b82f6', // B
                    '#f59e0b', // C
                    '#f97316', // D
                    '#ef4444'  // F
                ],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });


    /* ============================
       ATTENDANCE TREND CHART
    ============================ */
    new Chart(document.getElementById('attendanceChart'), {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            datasets: [{
                label: 'Attendance %',
                data: [92, 95, 93, 96, 94],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.2)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { min: 80, max: 100 } }
        }
    });


    /* ============================
       SUBJECT PERFORMANCE (HORIZONTAL BAR)
    ============================ */
    new Chart(document.getElementById('subjectChart'), {
        type: 'bar',
        data: {
            labels: ['Math', 'Science', 'English', 'History', 'PE'],
            datasets: [{
                label: 'Performance Score',
                data: [85, 78, 92, 74, 88],
                backgroundColor: '#3b82f6',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y', // <-- THIS MAKES IT HORIZONTAL
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { min: 0, max: 100 },
                y: { ticks: { autoSkip: false } }
            }
        }
    });

});
</script>

@endsection
