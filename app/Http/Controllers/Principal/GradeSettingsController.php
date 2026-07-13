<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\GradeSetting;
use Illuminate\Http\Request;

/**
 * Principal → Settings → Grades.
 *
 * A tabbed home for the grading thresholds/policies the Principal sets.
 * First tab: the passing threshold + promotion rule that Form 137 uses.
 */
class GradeSettingsController extends Controller
{
    public function index()
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless($schoolId, 404);

        return view('principal.settings.grades', [
            'settings' => GradeSetting::forSchool($schoolId),
        ]);
    }

    public function update(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless($schoolId, 404);

        $data = $request->validate([
            'passing_threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'promotion_rule'    => ['required', 'in:average,all_areas_pass'],
        ]);

        GradeSetting::forSchool($schoolId)->update($data);

        return back()->with('success', 'Grade settings saved.');
    }
}
