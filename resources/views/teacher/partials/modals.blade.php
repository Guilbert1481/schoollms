<div x-show="openAttendance" 
     x-transition.opacity
     class="fixed inset-0 z-[100] flex items-center justify-center p-4" 
     x-cloak>
    
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openAttendance = false"></div>

    <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full z-[110] overflow-hidden transform transition-all">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white">
            <h3 class="text-xl font-bold text-slate-800">Daily Absentees</h3>
            <button @click="openAttendance = false" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
        </div>
        <div class="p-6 bg-white overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-slate-400 text-xs uppercase tracking-widest border-b">
                    <tr><th class="pb-3">Student Name</th><th class="pb-3">Section</th></tr>
                </thead>
                <tbody class="divide-y">
                    <tr><td class="py-4 font-bold text-slate-700">Juan Dela Cruz</td><td class="py-4 text-slate-500">BSIT - 3A</td></tr>
                    <tr><td class="py-4 font-bold text-slate-700">Maria Clara</td><td class="py-4 text-slate-500">BSCS - 2B</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div x-show="openAtRisk" 
     x-transition.opacity
     class="fixed inset-0 z-[100] flex items-center justify-center p-4" 
     x-cloak>
    
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openAtRisk = false"></div>

    <div class="relative bg-white rounded-3xl shadow-2xl max-w-4xl w-full z-[110] overflow-hidden transform transition-all">
        <div class="p-6 border-b border-rose-100 flex justify-between items-center bg-rose-50">
            <h3 class="text-xl font-bold text-rose-800 uppercase">At-Risk Analysis</h3>
            <button @click="openAtRisk = false" class="text-rose-400 hover:text-rose-600 text-2xl">&times;</button>
        </div>
        <div class="p-6 bg-white overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-rose-300 text-[10px] uppercase tracking-widest border-b">
                    <tr><th class="pb-3">Student</th><th class="pb-3">Subject</th><th class="pb-3">Risk Factor</th><th class="pb-3">Grade</th></tr>
                </thead>
                <tbody class="divide-y divide-rose-50">
                    <tr>
                        <td class="py-4 font-bold text-slate-800">Mac Do</td>
                        <td class="py-4 text-slate-500">Computer Science</td>
                        <td class="py-4 text-rose-600 font-bold">3 Absences</td>
                        <td class="py-4 font-black text-rose-700">55%</td>
                    </tr>
                    <tr>
                        <td class="py-4 font-bold text-slate-800">Julie Bee</td>
                        <td class="py-4 text-slate-500">Math</td>
                        <td class="py-4 text-rose-600 font-bold">Prelim</td>
                        <td class="py-4 font-black text-rose-700">70%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Grade Distribution Modal -->


<div 
    x-show="openGradeModal"
    x-transition.opacity
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    x-cloak
    style="display: none;"
>
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
         @click="openGradeModal = false"></div>

    <div class="relative bg-white rounded-3xl shadow-2xl max-w-4xl w-full z-[110] overflow-hidden transform transition-all">
        
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="text-xl font-bold text-slate-800 italic">Grade Distribution Analysis</h3>
            <button @click="openGradeModal = false" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
        </div>

        <div class="p-6 bg-white max-h-[70vh] overflow-y-auto overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="text-slate-400 text-xs uppercase tracking-widest border-b">
                    <tr>
                        <th class="pb-3 px-2">Student Name</th>
                        <th class="pb-3 px-2">Subject</th>
                        <th class="pb-3 px-2">Grade</th>
                        <th class="pb-3 px-2">Status</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700 divide-y">
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-2 font-bold text-slate-800">Juan Dela Cruz</td>
                        <td class="py-4 px-2 text-slate-500">Mathematics</td>
                        <td class="py-4 px-2 font-black text-slate-800 text-lg">95</td>
                        <td class="py-4 px-2"><span class="px-2 py-1 rounded bg-green-50 text-green-600 font-bold text-xs uppercase">Excellent</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-2 font-bold text-slate-800">Maria Santos</td>
                        <td class="py-4 px-2 text-slate-500">Science</td>
                        <td class="py-4 px-2 font-black text-slate-800 text-lg">87</td>
                        <td class="py-4 px-2"><span class="px-2 py-1 rounded bg-blue-50 text-blue-600 font-bold text-xs uppercase">Good</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-2 font-bold text-slate-800">Mark Villanueva</td>
                        <td class="py-4 px-2 text-slate-500">English</td>
                        <td class="py-4 px-2 font-black text-slate-800 text-lg">76</td>
                        <td class="py-4 px-2"><span class="px-2 py-1 rounded bg-amber-50 text-amber-600 font-bold text-xs uppercase">Pass</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-2 font-bold text-slate-800">Ana Lopez</td>
                        <td class="py-4 px-2 text-slate-500">History</td>
                        <td class="py-4 px-2 font-black text-slate-800 text-lg">65</td>
                        <td class="py-4 px-2"><span class="px-2 py-1 rounded bg-red-50 text-red-600 font-bold text-xs uppercase">Failing</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-2 font-bold text-slate-800">Carlos Reyes</td>
                        <td class="py-4 px-2 text-slate-500">PE</td>
                        <td class="py-4 px-2 font-black text-slate-800 text-lg text-red-600">52</td>
                        <td class="py-4 px-2"><span class="px-2 py-1 rounded bg-red-50 text-red-600 font-bold text-xs uppercase">Failing</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="p-4 bg-slate-50 border-t border-slate-100 text-right">
            <button @click="openGradeModal = false" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-100 transition-colors">Close Report</button>
        </div>
    </div>
</div>