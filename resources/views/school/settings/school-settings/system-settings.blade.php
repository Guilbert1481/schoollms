@extends('layouts.app')

@section('content')

<div class="max-w-6xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    @include('school.settings.partials.school-settings._header')

    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.school_settings')
    ])
    
    {{-- System Settings Card --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-6">

        <form method="POST" action="{{ route('school.settings.system.save') }}" enctype="multipart/form-data">
        @csrf
        

        <div class="grid grid-cols-2 gap-4">

            <!-- Session Timeout -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_session_timeout" onclick="toggleField('session_timeout_field')">
                    <span class="font-bold">Session Timeout</span>
                </label>
                <div id="session_timeout_field" class="hidden mt-2">
                    <input type="number" name="session_timeout"
                        class="w-full border rounded-lg px-3 py-2"
                        value="{{ $settings->session_timeout ?? 15 }}">
                </div>
            </div>

            <!-- Pagination -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_pagination" onclick="toggleField('pagination_field')">
                    <span class="font-bold">Pagination</span>
                </label>
                <div id="pagination_field" class="hidden mt-2">
                    <select name="pagination" class="w-full border rounded-lg px-3 py-2">
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                    </select>
                </div>
            </div>

            <!-- Backup -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_backup" onclick="toggleField('backup_field')">
                    <span class="font-bold">Backup Schedule</span>
                </label>
                <div id="backup_field" class="hidden mt-2">
                    <select name="backup_schedule" class="w-full border rounded-lg px-3 py-2">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                    </select>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_maintenance" onclick="toggleField('maintenance_field')">
                    <span class="font-bold">Maintenance Mode</span>
                </label>
                <div id="maintenance_field" class="hidden mt-2">
                    <input type="checkbox" name="maintenance_mode" value="1"
                        {{ ($settings->maintenance_mode ?? false) ? 'checked' : '' }}>
                </div>
            </div>

            <!-- Timezone -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_timezone" onclick="toggleField('timezone_field')">
                    <span class="font-bold">Timezone</span>
                </label>
                <div id="timezone_field" class="hidden mt-2">
                    <select name="timezone" class="w-full border rounded-lg px-3 py-2">
                        @php
                            $timezones = timezone_identifiers_list();
                            $selectedTimezone = $settings->timezone ?? 'Asia/Manila';
                        @endphp

                        @foreach($timezones as $tz)
                            <option value="{{ $tz }}" {{ $selectedTimezone == $tz ? 'selected' : '' }}>
                                {{ $tz }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Date Format -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_date_format" onclick="toggleField('date_format_field')">
                    <span class="font-bold">Date Format</span>
                </label>
                <div id="date_format_field" class="hidden mt-2">
                    <select name="date_format" class="w-full border rounded-lg px-3 py-2">
                        @php
                            $formats = [
                                'Y-m-d' => date('Y-m-d'),
                                'm/d/Y' => date('m/d/Y'),
                                'd/m/Y' => date('d/m/Y'),
                                'F d, Y' => date('F d, Y'),
                                'M d, Y' => date('M d, Y'),
                            ];
                            $selectedFormat = $settings->date_format ?? 'Y-m-d';
                        @endphp

                        @foreach($formats as $format => $example)
                            <option value="{{ $format }}" {{ $selectedFormat == $format ? 'selected' : '' }}>
                                {{ $format }} ({{ $example }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- School Name -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_school_name" onclick="toggleField('school_name_field')">
                    <span class="font-bold">School Name</span>
                </label>
                <div id="school_name_field" class="hidden mt-2">
                    <input type="text" name="school_name"
                        class="w-full border rounded-lg px-3 py-2"
                        value="{{ $settings->school_name ?? '' }}">
                </div>
            </div>

            <!-- Upload Limit -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_upload_limit" onclick="toggleField('upload_limit_field')">
                    <span class="font-bold">Upload Limit</span>
                </label>
                <div id="upload_limit_field" class="hidden mt-2">
                    <input type="number" name="upload_limit"
                        class="w-full border rounded-lg px-3 py-2"
                        value="{{ $settings->upload_limit ?? 10 }}">
                </div>
            </div>

            <!-- Allowed File Types -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_allowed_file_types" onclick="toggleField('file_types_field')">
                    <span class="font-bold">Allowed File Types</span>
                </label>
                <div id="file_types_field" class="hidden mt-2">
                    <input type="text" name="allowed_file_types"
                        class="w-full border rounded-lg px-3 py-2"
                        value="{{ $settings->allowed_file_types ?? '' }}"
                        placeholder="jpg,png,pdf,docx">
                </div>
            </div>

            <!-- System Logo -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_logo" onclick="toggleField('logo_field')">
                    <span class="font-bold">System Logo</span>
                </label>
                <div id="logo_field" class="hidden mt-2">
                    <input type="file" name="system_logo">
                </div>
            </div>

            <!-- SMTP Host -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_smtp" onclick="toggleField('smtp_field')">
                    <span class="font-bold">SMTP Host</span>
                </label>
                <div id="smtp_field" class="hidden mt-2">
                    <input type="text" name="smtp_host"
                        class="w-full border rounded-lg px-3 py-2"
                        value="{{ $settings->smtp_host ?? '' }}">
                </div>
            </div>

            <!-- SMS API -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_sms" onclick="toggleField('sms_field')">
                    <span class="font-bold">SMS API</span>
                </label>
                <div id="sms_field" class="hidden mt-2">
                    <input type="text" name="sms_api"
                        class="w-full border rounded-lg px-3 py-2"
                        value="{{ $settings->sms_api ?? '' }}">
                </div>
            </div>

            <!-- Add Role -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_roles" onclick="toggleField('role_field')">
                    <span class="font-bold">Add Role</span>
                </label>

                <div id="role_field" class="hidden mt-2 space-y-2">
                    <div id="role_container">
                        <input type="text" name="roles[]"
                            class="w-full border rounded-lg px-3 py-2"
                            placeholder="Enter role name">
                    </div>

                    <button type="button"
                        onclick="addRoleField()"
                        class="px-3 py-1 border border-slate-300 bg-slate-100 rounded-md">
                        Add Another Role
                    </button>
                </div>
            </div>
            <!-- Add Office -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_offices" onclick="toggleField('office_field')">
                    <span class="font-bold">Add Office</span>
                </label>

                <div id="office_field" class="hidden mt-2 space-y-2">
                    <div id="office_container">
                        <input type="text" name="offices[]"
                            class="w-full border rounded-lg px-3 py-2"
                            placeholder="Enter office name">
                    </div>

                    <button type="button"
                        onclick="addOfficeField()"
                        class="px-3 py-1 border border-slate-300 bg-slate-100 rounded-md">
                        Add Another Office
                    </button>
                </div>
            </div>
            <!-- Add Group -->
            <div class="border rounded-lg p-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_groups" onclick="toggleField('group_field')">
                    <span class="font-bold">Add Group</span>
                </label>

                <div id="group_field" class="hidden mt-2 space-y-2">
                    <div id="group_container">
                        <input type="text" name="groups[]"
                            class="w-full border rounded-lg px-3 py-2"
                            placeholder="Enter group name">
                    </div>

                    <button type="button"
                        onclick="addGroupField()"
                        class="px-3 py-1 border border-slate-300 bg-slate-100 rounded-md">
                        Add Another Group
                    </button>
                </div>
            </div>

        </div>
        
        <!-- Save Button -->
        <div class="pt-6">
            <button type="submit"
                class="px-4 py-2 border border-slate-300 bg-slate-100 text-slate-700 rounded-md hover:bg-slate-300">
                Save Settings
            </button>
        </div>

        </form>

    </div>

</div>

<!-- Scripts -->
<script>
function toggleField(id) {
    const el = document.getElementById(id);
    el.classList.toggle('hidden');
}

function addRoleField() {
    const container = document.getElementById('role_container');

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'roles[]';
    input.placeholder = 'Enter role name';
    input.className = 'w-full border rounded-lg px-3 py-2 mt-2';

    container.appendChild(input);
}
</script>
<script>
function toggleField(id) {
    const el = document.getElementById(id);
    el.classList.toggle('hidden');
}

function addRoleField() {
    const container = document.getElementById('role_container');

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'roles[]';
    input.placeholder = 'Enter role name';
    input.className = 'w-full border rounded-lg px-3 py-2 mt-2';

    container.appendChild(input);
}

function addOfficeField() {
    const container = document.getElementById('office_container');

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'offices[]';
    input.placeholder = 'Enter office name';
    input.className = 'w-full border rounded-lg px-3 py-2 mt-2';

    container.appendChild(input);
}

function addGroupField() {
    const container = document.getElementById('group_container');

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'groups[]';
    input.placeholder = 'Enter group name';
    input.className = 'w-full border rounded-lg px-3 py-2 mt-2';

    container.appendChild(input);
}
</script>

@endsection