<?php

namespace App\Http\Controllers\Public;

use App\Models\Program;
use App\Models\Term;
use Illuminate\Http\Request;

/**
 * Senior High School enrolment wizard.
 *
 * Reuses the family / background / section / review steps from
 * AbstractWizardEnrollmentController; overrides Step 5 with strand
 * + grade-level selection (Grade 11 / 12).
 *
 * Approval routing (handled by ApprovalRouterService):
 *   continuing & regular  → auto
 *   anything else         → subject_coordinator
 */
class ShsEnrollmentController extends AbstractWizardEnrollmentController
{
    protected string $track       = 'shs';
    protected string $viewNs      = 'acad_enrolment.shs';
    protected string $routePrefix = 'public.apply.shs';

    /** Standard DepEd SHS strands. Schools can extend via config later. */
    public const STRANDS = [
        'STEM'  => 'Science, Technology, Engineering & Mathematics',
        'ABM'   => 'Accountancy, Business & Management',
        'HUMSS' => 'Humanities & Social Sciences',
        'GAS'   => 'General Academic Strand',
        'TVL'   => 'Technical-Vocational-Livelihood',
        'ARTS'  => 'Arts & Design',
        'SPORTS'=> 'Sports Track',
    ];

    public const GRADE_LEVELS = ['Grade 11', 'Grade 12'];

    public function showStep5($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();

        $programs = Program::query()
            ->where('school_id', $student->school_id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view("{$this->viewNs}.step5_strand", [
            'term'     => $term,
            'student'  => $student,
            'programs' => $programs,
            'strands'  => self::STRANDS,
            'grades'   => self::GRADE_LEVELS,
            'draft'    => $this->programDraft($term->id),
        ]);
    }

    public function storeStep5(Request $request, $termId)
    {
        $term = Term::findOrFail($termId);
        $this->requireStudent();

        $data = $request->validate([
            'program_id'      => 'required|exists:programs,id',
            'strand'          => 'required|string|in:'.implode(',', array_keys(self::STRANDS)),
            'academic_level'  => 'required|in:Grade 11,Grade 12',
            'enrollee_type'   => 'required|in:new,continuing,transferee,returnee',
            'program_type'    => 'required|in:regular,bridging,non_degree',
        ]);

        // SHS always carries the senior_high level.
        $data['education_level'] = 'senior_high';

        session()->put($this->sessionKey($term->id, 'program'), $data);

        return redirect()->route("{$this->routePrefix}.step6", $term->id);
    }
}
