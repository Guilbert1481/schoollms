<?php

namespace App\Http\Controllers\School\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\Modality;
use App\Models\SchoolProfile;

class SchoolController extends Controller
{
    public function indexSchool()
    {
        $schoolId = auth()->user()->school_id;

        $school = School::find($schoolId);
        $modalities = Modality::all();

        $profile = SchoolProfile::firstOrCreate([
            'school_id' => $schoolId
        ]);

        return view('school.settings.school-settings.school-settings', compact(
            'school',
            'profile',
            'modalities'
        ));
    }

    public function saveSchool(Request $request)
    {
        $schoolId = auth()->user()->school_id;
        $school = School::find($schoolId);

        $profile = SchoolProfile::firstOrCreate([
            'school_id' => $schoolId
        ]);

        $profile->school_name = $request->school_name;
        $profile->established_year = $request->established_year;
        $profile->address = $request->address;
        $profile->contact_number = $request->contact_number;
        $profile->mobile_number = $request->mobile_number;
        $profile->fax_number = $request->fax_number;
        $profile->website = $request->website;
        $profile->email = $request->email;
        $profile->motto = $request->motto;
        $profile->vision = $request->vision;
        $profile->mission = $request->mission;
        $profile->head_name = $request->head_name;
        $profile->head_title = $request->head_title;
        $profile->registrar_name = $request->registrar_name;
        $profile->registrar_title = $request->registrar_title;

        // Upload Logo
        if ($request->hasFile('school_logo')) {
            $path = $request->file('school_logo')->store('school_logos', 'public');
            $profile->school_logo = $path;
        }

        // Upload Seal
        if ($request->hasFile('school_seal')) {
            $path = $request->file('school_seal')->store('school_seals', 'public');
            $profile->school_seal = $path;
        }

        // Upload Hero Banner
        if ($request->hasFile('school_hero')) {
            $path = $request->file('school_hero')->store('school_heroes', 'public');
            $profile->school_hero = $path;
        }

        // Save Modalities
        if ($request->has('modalities')) {
            $school->modalities()->sync($request->modalities);
        } else {
            $school->modalities()->detach();
        }

        $profile->save();

        return back()->with('success', 'School settings saved successfully.');
    }
}