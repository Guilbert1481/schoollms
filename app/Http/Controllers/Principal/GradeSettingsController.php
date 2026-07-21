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
            'promotion_rule' => ['required', 'in:average,all_areas_pass'],
        ]);

        GradeSetting::forSchool($schoolId)->update($data);

        return back()->with('success', 'Grade settings saved.');
    }

    /**
     * Settings → Grades → Student Grade: which grade views students can see.
     * Each switch gates the matching student sidebar item and its route (the
     * middleware enforces access; the sidebar hides the link).
     */
    public function updateStudentVisibility(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless($schoolId, 404);

        GradeSetting::forSchool($schoolId)->update([
            'show_student_grades' => $request->boolean('show_student_grades'),
            'show_student_form137' => $request->boolean('show_student_form137'),
        ]);

        return back()
            ->with('success', 'Student grade visibility saved.')
            ->with('grades_tab', 'student_grade');
    }
}
