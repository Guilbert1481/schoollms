@extends('layouts.superadmin')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl w-full space-y-8 bg-white p-10 rounded-[2.5rem] shadow-2xl border border-purple-50">
        
        <div class="inline-block pb-1">
            <h2 class="text-2xl font-bold text-gray-800 tracking-tight">
                Edit Institution Profile
            </h2>
            <div class="h-1 w-2/3 bg-[#5D56BD] mt-1 rounded-full"></div>
        </div>

        <form action="{{ route('superadmin.schools.update', $school->id) }}" method="POST" class="mt-8 space-y-8">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Institution Name</label>
                    <input type="text" name="name" value="{{ old('name', $school->name) }}" 
                        class="w-full px-5 py-3 rounded-2xl border border-blue-50 bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-50 transition-all outline-none text-gray-600 shadow-sm">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Contact Person</label>
                    <input type="text" name="contact_person" value="Tabot" 
                        class="w-full px-5 py-3 rounded-2xl border border-blue-50 bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-50 transition-all outline-none text-gray-600 shadow-sm">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Mobile Number</label>
                    <input type="text" name="mobile" value="09123456789" 
                        class="w-full px-5 py-3 rounded-2xl border border-blue-50 bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-50 transition-all outline-none text-gray-600 shadow-sm">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Email Address</label>
                    <input type="email" name="email" value="tabot@gmail.com" 
                        class="w-full px-5 py-3 rounded-2xl border border-blue-50 bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-50 transition-all outline-none text-gray-600 shadow-sm">
                </div>

                <div class="space-y-1">
                <div class="flex justify-between items-center px-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Institution Plan</label>
                    <button type="button" id="unlockPlanBtn" onclick="unlockPlan()" class="text-[9px] font-bold text-indigo-600 hover:text-indigo-800 uppercase tracking-tighter transition-colors">
                        <i class="fas fa-edit mr-1"></i> Change Plan
                    </button>
                </div>
                
                <div id="planContainer" class="relative">
                    <div id="lockedPlan" class="flex items-center">
                        <input type="text" value="{{ strtoupper($school->plan_name ?? 'BASIC') }}" readonly 
                            class="w-full px-5 py-3 rounded-2xl bg-gray-50 border border-gray-100 text-gray-400 font-semibold cursor-not-allowed outline-none shadow-inner">
                        <i class="fas fa-lock absolute right-5 text-gray-300"></i>
                    </div>

                    <select id="unlockedPlan" name="pricing_id" class="hidden w-full px-5 py-3 rounded-2xl border-2 border-indigo-400 bg-white focus:ring-4 focus:ring-indigo-50 transition-all outline-none text-gray-700 shadow-md">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ $school->pricing_id == $plan->id ? 'selected' : '' }}>
                                {{ strtoupper($plan->plan_name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Institution ID</label>
                    <div class="relative flex items-center">
                        <input type="text" value="#{{ $school->id }}" readonly 
                            class="w-full px-5 py-3 rounded-2xl bg-gray-50 border border-gray-100 text-gray-400 font-semibold cursor-not-allowed outline-none shadow-inner">
                        <i class="fas fa-lock absolute right-5 text-gray-300"></i>
                    </div>
                </div>

                <div class="md:col-span-2 space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Physical Address</label>
                    <div class="relative">
                        <input type="text" name="address" placeholder="Physical Address"
                            class="w-full px-5 py-3 rounded-2xl border border-blue-50 bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-50 transition-all outline-none text-gray-600 shadow-sm">
                        <i class="fas fa-info-circle absolute right-5 top-4 text-gray-300"></i>
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center space-x-6 pt-10">
                <a href="{{ route('superadmin.schools.show', $school->id) }}" 
                    class="text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors px-6 py-2 rounded-full border border-transparent hover:border-gray-200">
                    Cancel
                </a>
                <button type="submit" 
                    class="px-10 py-3 bg-[#5D56BD] text-white font-bold rounded-full shadow-lg shadow-indigo-100 hover:shadow-indigo-200 hover:-translate-y-0.5 transition-all active:scale-95">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function unlockPlan() {
        const lockedDiv = document.getElementById('lockedPlan');
        const unlockedSelect = document.getElementById('unlockedPlan');
        const btn = document.getElementById('unlockPlanBtn');

        lockedDiv.classList.add('hidden');
        unlockedSelect.classList.remove('hidden');
        btn.classList.add('hidden'); // Hide the button after use
    }
</script>
@endsection