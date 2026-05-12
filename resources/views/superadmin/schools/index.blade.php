@extends('layouts.superadmin')

@section('content')
<div class="max-w-[1600px] mx-auto px-6 py-8">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Partner Institutions</h2>
            <p class="text-slate-500 text-sm mt-1">Manage registered schools and freelancers.</p>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pl-5 text-sm">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Shared Table --}}
    <x-table.table
        tableKey="superadmin_schools"
        :columns="$columns"
        :data="$data"
        :actions="config('tables.table-actions.superadmin_schools')"
        createModal="schoolCreateModal"
        createLabel="Add New Partner"
        deleteRoute="superadmin.schools.destroy"
    />

    {{-- Create Modal (Draggable) --}}
    <x-modal.form id="schoolCreateModal" title="Register New Partner" widthClass="w-[560px]">
        <form method="POST" action="{{ route('superadmin.schools.store') }}">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-sm mb-1">{{ $labels['school_name'] }}</label>
                    <input type="text" name="school_name" class="border p-2 w-full rounded" required>
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['code'] }}</label>
                    <input type="text" name="code" class="border p-2 w-full rounded">
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['domain'] }}</label>
                    <input type="text" name="domain" class="border p-2 w-full rounded">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm mb-1">{{ $labels['slug'] }}</label>
                    <input type="text" name="slug"
                           pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                           placeholder="e.g. memory-ridge"
                           class="border p-2 w-full rounded">
                    <p class="text-xs text-slate-400 mt-1">
                        Lowercase letters, numbers, and dashes only. Used in school URLs (e.g. <code>/memory-ridge/login</code>). Leave blank to auto-generate.
                    </p>
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['country'] }}</label>
                    <select name="country" class="border p-2 w-full rounded">
                        <option value="">-- Select Country --</option>
                        <option value="Philippines">Philippines</option>
                        <option value="United States">United States</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="Canada">Canada</option>
                        <option value="Australia">Australia</option>
                        <option value="Singapore">Singapore</option>
                        <option value="Malaysia">Malaysia</option>
                        <option value="Indonesia">Indonesia</option>
                        <option value="Thailand">Thailand</option>
                        <option value="Vietnam">Vietnam</option>
                        <option value="Japan">Japan</option>
                        <option value="South Korea">South Korea</option>
                        <option value="China">China</option>
                        <option value="India">India</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['type'] }}</label>
                    <select name="type" class="border p-2 w-full rounded" required>
                        <option value="school">School</option>
                        <option value="freelance">Freelance</option>
                    </select>
                </div>

                <div class="col-span-2 pt-3 border-t mt-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Primary Admin</p>
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['first_name'] }}</label>
                    <input type="text" name="first_name" class="border p-2 w-full rounded" required>
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['last_name'] }}</label>
                    <input type="text" name="last_name" class="border p-2 w-full rounded" required>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm mb-1">{{ $labels['email'] }}</label>
                    <input type="email" name="email" class="border p-2 w-full rounded" required>
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['password'] }}</label>
                    <input type="password" name="password" class="border p-2 w-full rounded" required minlength="8">
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['password_confirmation'] }}</label>
                    <input type="password" name="password_confirmation" class="border p-2 w-full rounded" required minlength="8">
                </div>
            </div>
        </form>
    </x-modal.form>

    {{-- Edit Modal (Draggable) --}}
    <x-modal.form id="schoolEditModal" title="Edit Institution" widthClass="w-[560px]">
        <form id="schoolEditForm" method="POST" action="">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-sm mb-1">{{ $labels['school_name'] }}</label>
                    <input type="text" name="school_name" class="border p-2 w-full rounded" required>
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['code'] }}</label>
                    <input type="text" name="code" class="border p-2 w-full rounded">
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['domain'] }}</label>
                    <input type="text" name="domain" class="border p-2 w-full rounded">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm mb-1">{{ $labels['slug'] }}</label>
                    <input type="text" name="slug"
                           pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                           class="border p-2 w-full rounded">
                    <p class="text-xs text-slate-400 mt-1">Lowercase letters, numbers, and dashes only.</p>
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['country'] }}</label>
                    <select name="country" class="border p-2 w-full rounded">
                        <option value="">-- Select Country --</option>
                        <option value="Philippines">Philippines</option>
                        <option value="United States">United States</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="Canada">Canada</option>
                        <option value="Australia">Australia</option>
                        <option value="Singapore">Singapore</option>
                        <option value="Malaysia">Malaysia</option>
                        <option value="Indonesia">Indonesia</option>
                        <option value="Thailand">Thailand</option>
                        <option value="Vietnam">Vietnam</option>
                        <option value="Japan">Japan</option>
                        <option value="South Korea">South Korea</option>
                        <option value="China">China</option>
                        <option value="India">India</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ $labels['type'] }}</label>
                    <select name="type" class="border p-2 w-full rounded" required>
                        <option value="school">School</option>
                        <option value="freelance">Freelance</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm mb-1">Plan</label>
                    <input type="text" name="plan_name" class="border p-2 w-full rounded">
                </div>

                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="is_active" value="1" id="schoolEditActive" class="w-4 h-4">
                    <label for="schoolEditActive" class="text-sm">Active</label>
                </div>
            </div>
        </form>
    </x-modal.form>

    {{-- Add-ons / Modules Modal --}}
    <div x-data="modulesModalData()"
         @open-modules-modal.window="openModal($event.detail.schoolId)"
         @keydown.escape.window="open = false"
         x-show="open"
         x-cloak
         style="display:none; position:fixed; inset:0; z-index:9999; overflow-y:auto;">

        <div style="position:fixed; inset:0; background:rgba(15,23,42,0.5); backdrop-filter:blur(6px);"
             @click="open = false"></div>

        <div style="display:flex; align-items:center; justify-content:center; min-height:100vh; padding:2rem;">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                    <div>
                        <h3 class="text-lg font-bold">Module Subscriptions</h3>
                        <p class="text-xs opacity-80">School ID: <span x-text="currentSchoolId"></span></p>
                    </div>
                    <button @click="open = false" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30">&times;</button>
                </div>

                <div class="p-6 max-h-[28rem] overflow-y-auto">
                    <template x-for="module in currentModules" :key="module.id">
                        <div class="border rounded-lg p-4 mb-3 flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-slate-800" x-text="module.name"></h4>
                                <p class="text-xs text-slate-400">Module #<span x-text="module.id"></span></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold"
                                  :class="statusClass(module)"
                                  x-text="statusText(module)"></span>
                        </div>
                    </template>

                    <template x-if="currentModules.length === 0">
                        <p class="text-center text-slate-400 italic py-8">No add-ons for this school.</p>
                    </template>
                </div>

                <div class="flex justify-end gap-2 px-6 py-4 border-t bg-slate-50">
                    <button @click="open = false" class="px-4 py-2 rounded bg-indigo-600 text-white">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script src="{{ asset('js/modules/draggable.js') }}"></script>
<script src="{{ asset('js/modules/table-pagination.js') }}"></script>
<script src="{{ asset('js/table/table-columns.js') }}"></script>

<script>
function openSchoolModulesModal(schoolId) {
    window.dispatchEvent(new CustomEvent('open-modules-modal', { detail: { schoolId } }));
}

const schoolsData = @json($schools->keyBy('id'));

function openSchoolEditModal(schoolId) {
    const s = schoolsData[schoolId];
    if (!s) return;

    const modal = document.getElementById('schoolEditModal');
    const form  = document.getElementById('schoolEditForm');

    form.action = '{{ url('superadmin/schools') }}/' + schoolId;

    form.querySelector('[name="school_name"]').value = s.school_name ?? '';
    form.querySelector('[name="code"]').value        = s.code ?? '';
    form.querySelector('[name="domain"]').value      = s.domain ?? '';
    form.querySelector('[name="slug"]').value        = s.slug ?? '';
    form.querySelector('[name="country"]').value     = s.country ?? '';
    form.querySelector('[name="type"]').value        = (s.type || 'school').toLowerCase();
    form.querySelector('[name="plan_name"]').value   = s.plan_name ?? '';
    form.querySelector('[name="is_active"]').checked = !!s.is_active;

    openModal('schoolEditModal');
}

function modulesModalData() {
    return {
        open: false,
        currentSchoolId: null,
        currentModules: [],
        allSchools: @json($schools->keyBy('id')),

        openModal(id) {
            this.currentSchoolId = id;
            this.currentModules = this.allSchools[id]?.modules || [];
            this.open = true;
        },

        daysLeft(m) {
            if (!m.pivot?.expires_at) return null;
            const diff = new Date(m.pivot.expires_at) - new Date();
            return Math.ceil(diff / (1000 * 60 * 60 * 24));
        },

        statusClass(m) {
            const d = this.daysLeft(m);
            if (d === null) return 'bg-emerald-100 text-emerald-700';
            if (d < 0) return 'bg-red-100 text-red-700';
            if (d <= 5) return 'bg-red-100 text-red-700';
            if (d <= 10) return 'bg-amber-100 text-amber-700';
            return 'bg-emerald-100 text-emerald-700';
        },

        statusText(m) {
            const d = this.daysLeft(m);
            if (d === null) return 'Active';
            if (d < 0) return 'Expired';
            if (d === 0) return 'Today';
            return d + ' Days';
        },
    };
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof makeDraggable === 'function') {
        makeDraggable('schoolCreateModal', 'schoolCreateModalHeader');
        makeDraggable('schoolEditModal', 'schoolEditModalHeader');
    }
    if (typeof initTable === 'function') {
        initTable('superadmin_schools');
    }
});
</script>
@endsection
