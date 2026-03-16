<div class="py-8 px-4 sm:px-6 lg:px-8">
    {{-- Section 1: Main Plans (4 Rectangles in 1 Row) --}}
    <div class="mb-12">
        <h2 class="text-xl font-black text-slate-800 mb-6 uppercase tracking-wider">Choose Your Base Plan</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <div class="relative bg-white p-6 rounded-3xl border-2 {{ auth()->user()->school->plan_name == 'basic' ? 'border-indigo-600 ring-4 ring-indigo-50' : 'border-slate-100' }} shadow-sm">
                @if(auth()->user()->school->plan_name == 'basic')
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase">Current Plan</span>
                @endif
                <h3 class="font-black text-slate-900 text-lg uppercase">Basic</h3>
                <p class="text-slate-500 text-xs mb-4">Perfect for Freelancers</p>
                <ul class="space-y-2 mb-6">
                    <li class="flex items-center text-xs text-slate-600"><span class="text-green-500 mr-2">✓</span> Student Management</li>
                    <li class="flex items-center text-xs text-slate-600"><span class="text-green-500 mr-2">✓</span> Teacher Dashboard</li>
                    <li class="flex items-center text-xs text-slate-400"><span class="text-slate-300 mr-2">✕</span> Exam Portal</li>
                </ul>
                <button class="w-full py-2 bg-slate-100 text-slate-600 font-bold rounded-xl text-xs hover:bg-slate-200 transition">Select Plan</button>
            </div>

            <div class="bg-white p-6 rounded-3xl border-2 border-slate-100 shadow-sm hover:border-indigo-200 transition">
                <h3 class="font-black text-slate-900 text-lg uppercase">Standard</h3>
                <p class="text-slate-500 text-xs mb-4">For Growing Schools</p>
                <ul class="space-y-2 mb-6 text-xs">
                    <li class="flex items-center text-slate-600"><span class="text-green-500 mr-2">✓</span> Everything in Basic</li>
                    <li class="flex items-center text-slate-600"><span class="text-green-500 mr-2">✓</span> Exam & Test Portal</li>
                    <li class="flex items-center text-slate-400"><span class="text-slate-300 mr-2">✕</span> Invoicing System</li>
                </ul>
                <button class="w-full py-2 bg-indigo-600 text-white font-bold rounded-xl text-xs hover:bg-indigo-700 transition">Upgrade</button>
            </div>

            <div class="bg-white p-6 rounded-3xl border-2 border-slate-100 shadow-sm hover:border-indigo-200 transition">
                <h3 class="font-black text-slate-900 text-lg uppercase">Premium</h3>
                <p class="text-slate-500 text-xs mb-4">Full Suite Control</p>
                <ul class="space-y-2 mb-6 text-xs">
                    <li class="flex items-center text-slate-600"><span class="text-green-500 mr-2">✓</span> Everything Standard</li>
                    <li class="flex items-center text-slate-600"><span class="text-green-500 mr-2">✓</span> Full CRM & Billing</li>
                    <li class="flex items-center text-slate-600"><span class="text-green-500 mr-2">✓</span> Advanced Reporting</li>
                </ul>
                <button class="w-full py-2 bg-indigo-600 text-white font-bold rounded-xl text-xs hover:bg-indigo-700 transition">Upgrade</button>
            </div>

            <div class="bg-slate-900 p-6 rounded-3xl border-2 border-slate-800 shadow-sm">
                <h3 class="font-black text-white text-lg uppercase">Enterprise</h3>
                <p class="text-slate-400 text-xs mb-4">Large Institutions</p>
                <ul class="space-y-2 mb-6 text-xs">
                    <li class="flex items-center text-white"><span class="text-indigo-400 mr-2">✓</span> Unlimited Access</li>
                    <li class="flex items-center text-white"><span class="text-indigo-400 mr-2">✓</span> Custom Domain</li>
                    <li class="flex items-center text-white"><span class="text-indigo-400 mr-2">✓</span> 24/7 Dedicated Support</li>
                </ul>
                <button class="w-full py-2 bg-white text-slate-900 font-bold rounded-xl text-xs hover:bg-slate-100 transition">Contact Sales</button>
            </div>
        </div>
    </div>

    {{-- Section 2: Add-Ons (Main Modules Checkboxes) --}}
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
        <h2 class="text-xl font-black text-slate-800 mb-2 uppercase tracking-wider">Module Add-Ons</h2>
        <p class="text-slate-500 text-sm mb-6">Enhance your school with specific modular features.</p>

        <form action="#" method="POST">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <label class="flex items-start p-4 rounded-2xl border-2 border-slate-50 hover:border-indigo-100 hover:bg-indigo-50/30 cursor-pointer transition group">
                    <input type="checkbox" name="modules[]" value="invoicing" class="mt-1 w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <div class="ml-4">
                        <span class="block font-bold text-slate-800 group-hover:text-indigo-900">Invoicing & Payments</span>
                        <span class="block text-xs text-slate-500">Automate tuition collections and receipts.</span>
                    </div>
                </label>

                <label class="flex items-start p-4 rounded-2xl border-2 border-slate-50 hover:border-indigo-100 hover:bg-indigo-50/30 cursor-pointer transition group">
                    <input type="checkbox" name="modules[]" value="library" class="mt-1 w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <div class="ml-4">
                        <span class="block font-bold text-slate-800 group-hover:text-indigo-900">Digital Library</span>
                        <span class="block text-xs text-slate-500">Document management for school resources.</span>
                    </div>
                </label>

                <label class="flex items-start p-4 rounded-2xl border-2 border-slate-50 hover:border-indigo-100 hover:bg-indigo-50/30 cursor-pointer transition group">
                    <input type="checkbox" name="modules[]" value="exam_portal" class="mt-1 w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <div class="ml-4">
                        <span class="block font-bold text-slate-800 group-hover:text-indigo-900">Student Exam Portal</span>
                        <span class="block text-xs text-slate-500">Secure environment for online assessments.</span>
                    </div>
                </label>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-2xl font-black text-sm hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
                    Update Modules & Plan
                </button>
            </div>
        </form>
    </div>
</div>