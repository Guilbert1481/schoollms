<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdmissionSettingsController extends Controller
{
    public function edit()
    {
        $school = auth()->user()->school;

        return view('staff.admissions.settings', compact('school'));
    }

    public function update(Request $request)
{
    $school = auth()->user()->school;

    $school->update([
        'requires_admission_test' => $request->requires_admission_test,
    ]);

    return back()->with('success', 'Settings updated successfully.');
}

}
