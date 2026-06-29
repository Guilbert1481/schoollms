<?php

namespace App\Http\Controllers\Staff\Registrar\Settings;

use App\Http\Controllers\Controller;
use App\Models\StudentIdSetting;
use Illuminate\Http\Request;

/**
 * Registrar → Settings → Student ID.
 * Controls how the Student Digital ID is displayed (orientation, barcode source,
 * whether it has a back). Orientation drives the student Profile tab layout.
 */
class StudentIdSettingController extends Controller
{
    public function edit()
    {
        $settings = StudentIdSetting::forSchool((int) auth()->user()->school_id);

        return view('registrar.settings.student_id', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'orientation'    => ['required', 'in:'.implode(',', StudentIdSetting::ORIENTATIONS)],
            'barcode_source' => ['required', 'in:'.implode(',', StudentIdSetting::BARCODE_SOURCES)],
            'show_back'      => ['nullable', 'boolean'],
        ]);

        StudentIdSetting::forSchool((int) auth()->user()->school_id)->update([
            'orientation'    => $data['orientation'],
            'barcode_source' => $data['barcode_source'],
            'show_back'      => $request->boolean('show_back'),
        ]);

        return back()->with('status', 'Student ID settings saved.');
    }
}
