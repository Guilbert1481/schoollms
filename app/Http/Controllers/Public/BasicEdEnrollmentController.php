<?php

namespace App\Http\Controllers\Public;

use App\Models\AcademicLevel;
use App\Models\Program;
use App\Models\Term;
use Illuminate\Http\Request;

class BasicEdEnrollmentController extends AbstractWizardEnrollmentController
{
    protected string $track       = 'basic';
    protected string $viewNs      = 'acad_enrolment.basic_ed';
    protected string $routePrefix = 'public.apply.basic';

    public function showStep5($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();

        $programs = Program::query()
            ->where('school_id', $student->school_id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $levels = AcademicLevel::query()
            ->where('school_id', $student->school_id)
            ->where('type', 'basic')
            ->orderBy('sequence_order')
            ->get(['id', 'name', 'sequence_order']);

        return view("{$this->viewNs}.step5_program", [
            'term'     => $term,
            'student'  => $student,
            'programs' => $programs,
            'levels'   => $levels,
            'draft'    => $this->programDraft($term->id),
        ]);
    }

    public function storeStep5(Request $request, $termId)
    {
        $term = Term::findOrFail($termId);
        $this->requireStudent();

        $data = $request->validate([
            'program_id'      => 'required|exists:programs,id',
            'education_level' => 'required|in:kinder,elementary,junior_high',
            'academic_level'  => 'nullable|string|max:64',
            'enrollee_type'   => 'required|in:new,continuing,transferee,returnee',
            'program_type'    => 'required|in:regular,bridging,non_degree',
        ]);

        session()->put($this->sessionKey($term->id, 'program'), $data);

        return redirect()->route("{$this->routePrefix}.step6", $term->id);
    }
}
