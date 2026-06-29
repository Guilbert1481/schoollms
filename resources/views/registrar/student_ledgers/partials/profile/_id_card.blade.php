@php $landscape = ($idCard['orientation'] ?? 'portrait') === 'landscape'; @endphp
<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <i data-lucide="id-card" class="h-4 w-4 text-indigo-600"></i>
            <h3 class="text-sm font-bold text-slate-800">Student Digital ID</h3>
        </div>
        <a href="{{ route('registrar.settings.student-id.edit') }}"
           class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">
            <i data-lucide="pencil" class="h-3.5 w-3.5"></i> Edit ID
        </a>
    </div>

    <div x-data="{ side: 'front' }" class="flex flex-col items-center">
        {{-- FRONT --}}
        <div x-show="side === 'front'"
             class="w-full {{ $landscape ? 'max-w-md' : 'max-w-[230px]' }} rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 shadow-sm">
            <div class="mb-3 text-center text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ $idCard['school_name'] }}</div>

            @if($landscape)
                <div class="flex items-center gap-4">
                    <div class="h-24 w-20 flex-none overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                        @if($idCard['photo'])
                            <img src="{{ asset('storage/'.$idCard['photo']) }}" class="h-full w-full object-cover" alt="">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-slate-300"><i data-lucide="user" class="h-8 w-8"></i></div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-extrabold text-slate-900">{{ $idCard['name'] }}</div>
                        <div class="text-xs font-semibold text-indigo-600">{{ $idCard['student_id'] }}</div>
                        <div class="text-xs text-slate-600">{{ $idCard['grade_section'] }}</div>
                    </div>
                </div>
            @else
                <div class="mx-auto mb-3 h-28 w-24 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                    @if($idCard['photo'])
                        <img src="{{ asset('storage/'.$idCard['photo']) }}" class="h-full w-full object-cover" alt="">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-slate-300"><i data-lucide="user" class="h-10 w-10"></i></div>
                    @endif
                </div>
                <div class="text-center">
                    <div class="text-sm font-extrabold text-slate-900">{{ $idCard['name'] }}</div>
                    <div class="text-xs font-semibold text-indigo-600">{{ $idCard['student_id'] }}</div>
                    <div class="text-xs text-slate-600">{{ $idCard['grade_section'] }}</div>
                </div>
            @endif

            <div class="mt-3 text-center font-mono text-xs tracking-[0.25em] text-slate-700">{{ $idCard['barcode'] }}</div>
        </div>

        {{-- BACK --}}
        @if($idCard['show_back'])
            <div x-show="side === 'back'" x-cloak
                 class="flex w-full {{ $landscape ? 'h-40 max-w-md' : 'h-72 max-w-[230px]' }} items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-center text-xs text-slate-400">
                Back of ID — to be designed in the ID builder.
            </div>
        @endif

        <p class="mt-2 text-center text-[11px] text-slate-400">(Click ID to view full size)</p>

        @if($idCard['show_back'])
            <div class="mt-2 flex gap-2">
                <button type="button" @click="side = 'front'"
                        :class="side === 'front' ? 'bg-indigo-600 text-white' : 'border border-slate-300 bg-white text-slate-600'"
                        class="rounded-lg px-4 py-1.5 text-xs font-semibold">Front</button>
                <button type="button" @click="side = 'back'"
                        :class="side === 'back' ? 'bg-indigo-600 text-white' : 'border border-slate-300 bg-white text-slate-600'"
                        class="rounded-lg px-4 py-1.5 text-xs font-semibold">Back</button>
            </div>
        @endif
    </div>
</div>
