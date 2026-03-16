<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolController extends Controller
{
    /**
     * Show Branding Settings Page
     */
    public function showBranding()
    {
        $school = auth()->user()->school;

        if (!$school) {
            abort(403, 'No associated school.');
        }

        return view('admin.settings.branding', compact('school'));
    }

    /**
     * Update School Branding
     */
    public function updateBranding(Request $request)
    {
        $school = auth()->user()->school;

        if (!$school) {
            abort(403, 'No associated school.');
        }

        $request->validate([
            'school_name'   => 'required|string|max:255',
            'sidebar_color' => 'required|string|max:20',
            'school_logo'   => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        // 🔹 Handle Logo Upload
        if ($request->hasFile('school_logo')) {

            // Delete old logo if exists
            if ($school->school_logo && Storage::disk('public')->exists($school->school_logo)) {
                Storage::disk('public')->delete($school->school_logo);
            }

            $logoPath = $request->file('school_logo')
                ->store('schools/logos', 'public');

            $school->school_logo = $logoPath;
        }

        // 🔹 Update Basic Branding
        $school->school_name   = $request->school_name;
        $school->sidebar_color = $request->sidebar_color;

        $school->save();

        return back()->with('success', 'Branding updated successfully.');
    }
}
