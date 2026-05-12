<?php

namespace App\Http\Controllers\School\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Models\Role;
use Illuminate\Support\Str;

class SystemController extends Controller
{
    public function indexSystem()
    {
        $schoolId = auth()->user()->school_id;

        $settings = SystemSetting::where('school_id', $schoolId)->first();

        return view('school.settings.school-settings.system-settings', compact('settings'));
    }

    public function saveSystem(Request $request)
{
    $schoolId = auth()->user()->school_id;
    $userId = auth()->id();

    // Get or create settings row
    $settings = SystemSetting::firstOrCreate([
        'school_id' => $schoolId
    ]);

    // Store original values before update
    $original = $settings->getOriginal();

    // Update only enabled fields
    if ($request->has('enable_session_timeout')) {
        $settings->session_timeout = $request->session_timeout;
    }

    if ($request->has('enable_pagination')) {
        $settings->pagination = $request->pagination;
    }

    if ($request->has('enable_timezone')) {
        $settings->timezone = $request->timezone;
    }

    if ($request->has('enable_date_format')) {
        $settings->date_format = $request->date_format;
    }

    if ($request->has('enable_upload_limit')) {
        $settings->upload_limit = $request->upload_limit;
    }

    if ($request->has('enable_allowed_file_types')) {
        $settings->allowed_file_types = $request->allowed_file_types;
    }

    if ($request->has('enable_backup')) {
        $settings->backup_schedule = $request->backup_schedule;
    }

    if ($request->has('enable_school_name')) {
        $settings->school_name = $request->school_name;
    }

    if ($request->has('enable_smtp')) {
        $settings->smtp_host = $request->smtp_host;
    }

    if ($request->has('enable_sms')) {
        $settings->sms_api = $request->sms_api;
    }

    if ($request->has('enable_maintenance')) {
        $settings->maintenance_mode = 1;
    }

    // Logo upload
    if ($request->hasFile('system_logo')) {
        $path = $request->file('system_logo')->store('logos', 'public');
        $settings->system_logo = $path;
    }

    // Save updated settings
    $settings->save();

    // Detect changes for history
    $changes = [];
    foreach ($settings->getChanges() as $field => $newValue) {
        $changes[$field] = [
            'old' => $original[$field] ?? null,
            'new' => $newValue
        ];
    }

    // Save history if there are changes
    if (!empty($changes)) {
        \App\Models\SystemSettingHistory::create([
            'school_id' => $schoolId,
            'updated_by' => $userId,
            'old_values' => $original,
            'new_values' => $settings->getAttributes()
        ]);
    }

    // Save roles
    if ($request->has('enable_roles') && $request->has('roles')) {
        foreach ($request->roles as $roleName) {
            if (!empty($roleName)) {
                $slug = \Illuminate\Support\Str::slug($roleName, '_');

                \App\Models\Role::firstOrCreate([
                    'name' => $slug,
                    'school_id' => $schoolId
                ]);
            }
        }
    }

    return back()->with('success', 'Settings and roles saved successfully.');
}
}