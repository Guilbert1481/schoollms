@extends('layouts.superadmin')

@section('content')
<div class="max-w-[1600px] mx-auto pb-24">

    {{-- SUCCESS MESSAGE --}}
    @if(session('status'))
        <div 
            x-data="{ show: true }" 
            x-show="show" 
            x-init="setTimeout(() => show = false, 2500)"
            class="mb-6 px-5 py-3 bg-emerald-100 text-emerald-700 font-semibold rounded-xl shadow"
        >
            {{ session('status') }}
        </div>
    @endif


    {{-- PAGE HEADER (unchanged) --}}
    <div class="mb-10 flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">Module Management</h2>
            <p class="text-slate-500 font-medium mt-1">Global feature control for your SaaS ecosystem.</p>
        </div>

        <div class="flex items-center gap-3">
            <button id="editBtn" 
                class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold shadow hover:bg-indigo-700 transition">
                Edit
            </button>

            <button id="saveBtn" 
                class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold shadow hover:bg-emerald-700 transition hidden">
                Save
            </button>

            <button id="cancelBtn" 
                class="px-5 py-2.5 rounded-xl bg-slate-300 text-slate-700 font-semibold shadow hover:bg-slate-400 transition hidden">
                Cancel
            </button>
        </div>
    </div>


    {{-- MODULE FORM --}}
    <form id="moduleForm" method="POST" action="{{ route('superadmin.schools.modules.update', $school->id) }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            @foreach($categories as $categoryName => $modules)
                <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden transition-all hover:shadow-md">

                    {{-- CATEGORY HEADER --}}
                    <div class="bg-slate-50/50 px-8 py-5 border-b border-slate-100">
                        <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">
                            {{ $categoryName }}
                        </h3>
                    </div>

                    {{-- MODULE LIST --}}
                    <div class="p-6 space-y-3">

                        @foreach($modules as $module)
                            <div class="flex items-center justify-between p-4 rounded-2xl hover:bg-slate-50 transition-colors group">

                                {{-- MODULE NAME --}}
                                <span class="font-semibold text-slate-700 text-sm leading-tight">
                                    {{ $module->name }}
                                </span>

                                {{-- TOGGLE --}}
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input 
                                        type="checkbox"
                                        class="module-toggle sr-only peer"
                                        name="modules[]"
                                        value="{{ $module->id }}"
                                        {{ in_array($module->id, $enabled) ? 'checked' : '' }}
                                        disabled
                                    >
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer 
                                                peer-checked:bg-indigo-600 transition-all"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full 
                                                peer-checked:translate-x-5 transition-all shadow"></div>
                                </label>

                            </div>
                        @endforeach

                    </div>

                </div>
            @endforeach

        </div>

    </form>
</div>


{{-- EDIT MODE SCRIPT --}}
<script>
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const toggles = document.querySelectorAll('.module-toggle');

    editBtn.onclick = () => {
        toggles.forEach(t => t.disabled = false);
        editBtn.classList.add('hidden');
        saveBtn.classList.remove('hidden');
        cancelBtn.classList.remove('hidden');
    };

    cancelBtn.onclick = () => {
        toggles.forEach(t => t.disabled = true);
        saveBtn.classList.add('hidden');
        cancelBtn.classList.add('hidden');
        editBtn.classList.remove('hidden');
    };

    saveBtn.onclick = () => {
        document.getElementById('moduleForm').submit();
    };
</script>

@endsection
