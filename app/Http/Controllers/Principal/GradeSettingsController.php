<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\GradeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Settings → Grades → Division: name the basic-ed grading periods. Stores
     * the collective noun (period_label) and the per-period display names. The
     * period stays an ordinal (1..N) — this only changes how it reads. Capped at
     * the grade pipeline's supported maximum.
     */
    public function updateDivision(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless($schoolId, 404);

        $data = $request->validate([
            'period_label' => ['required', 'string', 'max:40'],
            'period_names' => ['required', 'array', 'min:1', 'max:'.GradeSetting::MAX_PERIODS],
            // Two periods sharing a name render as indistinguishable options in every
            // picker, so a teacher could post to the wrong ordinal — forbid it.
            'period_names.*' => ['required', 'string', 'max:60', 'distinct:ignore_case'],
        ]);

        // Grade integrity: a period is an ordinal (1..N) already written onto
        // report_card_grades (posted finals) AND component_scores (draft scores).
        // Dropping the count below the highest occupied period would orphan those
        // records (and shift the labels of the rest), so refuse to reduce past
        // whatever grades — draft or posted — already exist for the school.
        $highestOccupied = max(
            (int) DB::table('report_card_grades')->where('school_id', $schoolId)->max('grading_period'),
            (int) DB::table('component_scores')->where('school_id', $schoolId)->max('grading_period'),
        );

        if (count($data['period_names']) < $highestOccupied) {
            return back()
                ->withInput()
                ->with('grades_tab', 'division')
                ->withErrors(['period_names' => "Teachers have already entered grades up to period {$highestOccupied}. Keep at least {$highestOccupied} periods — rename them instead of removing."]);
        }

        GradeSetting::forSchool($schoolId)->update([
            'period_label' => trim($data['period_label']),
            'period_names' => array_map('trim', $data['period_names']),
        ]);

        return back()
            ->with('success', 'Grading periods saved.')
            ->with('grades_tab', 'division');
    }
}
