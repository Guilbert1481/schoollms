<?php

namespace App\Http\Controllers\Public;

use App\Models\AcademicLevel;
use App\Models\ClassModel;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Program;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentEnrollmentSubject;
use App\Models\Term;
use App\Modules\AcadEnrolment\Services\ApprovalRouterService;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\EnrolmentValidationPipeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Higher Education **Regular** (block) enrolment wizard.
 *
 * Workflow:
 *   3 — Family
 *   4 — Academic Background
 *   5 — Program + Curriculum + Year Level + Semester + Enrollee Type
 *   6 — Block section pick (classes auto-derived from section)
 *   7 — Review (full pipeline runs against derived class IDs)
 *
 * Approval routing handled by ApprovalRouterService; for regular enrolment:
 *   - continuing + regular  → auto
 *   - transferee/returnee   → program_head
 *   - overload (>policy)    → dean
 */
class HigherEdRegularEnrollmentController extends AbstractWizardEnrollmentController
{
    protected string $track       = 'higher_regular';
    protected string $viewNs      = 'acad_enrolment.higher_ed_regular';
    protected string $routePrefix = 'public.apply.higher_regular';

    /** Run nearly the full pipeline; payment_gate only kicks in after billing. */
    protected array $skip = ['payment_gate'];

    /* ------------------------------------------------------------------ */
    /* Step 5 — Program + Curriculum + Year Level + Semester              */
    /* ------------------------------------------------------------------ */

    public function showStep5($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();

        $programs = Program::query()
            ->where('school_id', $student->school_id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $draft = $this->programDraft($term->id);

        // Curricula are filtered by chosen program (server-rendered all, JS filters).
        $curricula = Curriculum::query()
            ->where('school_id', $student->school_id)
            ->where('is_active', true)
            ->orderBy('program_id')
            ->orderBy('version')
            ->get(['id', 'program_id', 'name', 'version']);

        return view("{$this->viewNs}.step5_curriculum", [
            'term'      => $term,
            'student'   => $student,
            'programs'  => $programs,
            'curricula' => $curricula,
            'draft'     => $draft,
            'years'     => ['1', '2', '3', '4', '5'],
            'semesters' => ['1' => '1st Semester', '2' => '2nd Semester', '3' => 'Summer'],
        ]);
    }

    public function storeStep5(Request $request, $termId)
    {
        $term = Term::findOrFail($termId);
        $this->requireStudent();

        $data = $request->validate([
            'program_id'      => 'required|exists:programs,id',
            'year_level'      => 'required|integer|min:1|max:6',
            'semester'        => 'required|in:1,2,3',
            'education_level' => 'required|in:undergraduate,graduate',
            'enrollee_type'   => 'required|in:new,continuing,transferee,returnee',
            'program_type'    => 'required|in:regular,bridging,non_degree',
        ]);

        // Curriculum is no longer chosen by the student — it's an institutional
        // record that flows from program + academic year. Resolve the active
        // curriculum for this program automatically; downstream code
        // (totalUnitsForCurriculum, irregular class filter) still uses it.
        $data['curriculum_id'] = Curriculum::query()
            ->where('program_id', $data['program_id'])
            ->where('is_active', true)
            ->orderByDesc('version')
            ->value('id');

        session()->put($this->sessionKey($term->id, 'program'), $data);

        return redirect()->route("{$this->routePrefix}.step6", $term->id);
    }

    /* ------------------------------------------------------------------ */
    /* Step 6 — Block section pick (year-level filtered)                  */
    /* ------------------------------------------------------------------ */

    public function showStep6($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();
        $draft   = $this->programDraft($term->id);

        if (empty($draft['program_id'])) {
            return redirect()->route("{$this->routePrefix}.step5", $term->id);
        }

        $sections = Section::query()
            ->where('school_id', $student->school_id)
            ->where('program_id', $draft['program_id'])
            ->where('term_id', $term->id)
            ->where('year_level', $draft['year_level'])
            ->where('is_active', true)
            ->withCount('classes')
            ->orderBy('name')
            ->get();

        return view("{$this->viewNs}.step6_section", [
            'term'     => $term,
            'student'  => $student,
            'sections' => $sections,
            'draft'    => $draft,
            'pick'     => $this->sectionDraft($term->id),
        ]);
    }

    public function storeStep6(Request $request, $termId)
    {
        $term = Term::findOrFail($termId);
        $this->requireStudent();

        // Regular students MUST choose a block section (classes are derived from it).
        $data = $request->validate(['section_id' => 'required|exists:sections,id']);

        session()->put($this->sessionKey($term->id, 'section'), $data['section_id']);

        return redirect()->route("{$this->routePrefix}.step7", $term->id);
    }

    /* ------------------------------------------------------------------ */
    /* Step 7 — Review                                                     */
    /* ------------------------------------------------------------------ */

    public function showStep7($termId, EnrolmentValidationPipeline $pipeline, ApprovalRouterService $router)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();
        $program = $this->programDraft($term->id);

        if (empty($program['program_id'])) {
            return redirect()->route("{$this->routePrefix}.step5", $term->id);
        }

        $sectionId = $this->sectionDraft($term->id);
        if (!$sectionId) {
            return redirect()->route("{$this->routePrefix}.step6", $term->id);
        }

        $section     = Section::findOrFail($sectionId);
        $classes     = $this->classesForSection($section);
        $totalUnits  = $this->totalUnitsForCurriculum($program, $classes);
        $classIds    = $classes->pluck('id');

        $ctx      = $this->buildContext($student, $term, $program, $sectionId, $classIds);
        $result   = $pipeline->run($ctx, $this->skip);
        $approval = $router->route($ctx, (float) $totalUnits);

        return view("{$this->viewNs}.step7_review", [
            'term'        => $term,
            'student'     => $student,
            'program'     => $program,
            'section'     => $section,
            'classes'     => $classes,
            'totalUnits'  => $totalUnits,
            'parents'     => $student->guardians()->whereIn('type', ['parent', 'guardian'])->get(),
            'emergency'   => $student->guardians()->where('is_emergency_contact', true)->first(),
            'backgrounds' => $student->academicBackgrounds()->orderByDesc('year_ended')->get(),
            'result'      => $result,
            'approval'    => $approval,
        ]);
    }

    public function submit($termId, Request $request, EnrolmentValidationPipeline $pipeline, ApprovalRouterService $router)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();
        $program = $this->programDraft($term->id);

        if (empty($program['program_id'])) {
            return redirect()->route("{$this->routePrefix}.step5", $term->id);
        }

        $sectionId = $this->sectionDraft($term->id);
        if (!$sectionId) {
            return redirect()->route("{$this->routePrefix}.step6", $term->id);
        }

        $section    = Section::findOrFail($sectionId);
        $classes    = $this->classesForSection($section);
        $totalUnits = $this->totalUnitsForCurriculum($program, $classes);
        $classIds   = $classes->pluck('id');

        $ctx    = $this->buildContext($student, $term, $program, $sectionId, $classIds);
        $result = $pipeline->run($ctx, $this->skip);

        if (!$result->passed()) {
            return redirect()->route("{$this->routePrefix}.step7", $term->id)
                ->withErrors(['enrolment' => collect($result->failures())->pluck('message')->all()]);
        }

        $approval = $router->route($ctx, (float) $totalUnits);

        $enrollment = DB::transaction(function () use ($student, $term, $program, $sectionId, $classes, $totalUnits, $approval, $result) {
            $en = StudentEnrollment::create([
                'school_id'        => $student->school_id,
                'student_id'       => $student->id,
                'academic_year_id' => $term->academic_year_id,
                'term_id'          => $term->id,
                'program_id'       => $program['program_id'],
                'education_level'  => $program['education_level'],
                'program_type'     => $program['program_type'] ?? 'regular',
                'enrollee_type'    => $program['enrollee_type'] ?? 'new',
                'section_id'       => $sectionId,
                'total_units'      => $totalUnits,
                'status'           => StudentEnrollment::STATUS_SUBMITTED,
                'approval_level'   => $approval,
                'remarks'          => $this->summariseRemarks($result, $program),
            ]);

            foreach ($classes as $class) {
                StudentEnrollmentSubject::create([
                    'student_enrollment_id' => $en->id,
                    'class_id'              => $class->id,
                    'subject_id'            => $class->subject_id,
                    'status'                => StudentEnrollmentSubject::STATUS_ENROLLED,
                ]);
            }

            return $en;
        });

        session()->forget($this->sessionKey($term->id));

        return redirect()->route("{$this->routePrefix}.confirmation", [
            'term'       => $term->id,
            'enrollment' => $enrollment->id,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Higher-Ed–specific helpers                                          */
    /* ------------------------------------------------------------------ */

    /** Classes attached to the chosen block section (one row per subject). */
    protected function classesForSection(Section $section)
    {
        return ClassModel::query()
            ->with('subject:id,name,code')
            ->where('section_id', $section->id)
            ->where('is_open', true)
            ->orderBy('code')
            ->get(['id', 'subject_id', 'code', 'room', 'schedule', 'capacity']);
    }

    /**
     * Sum units from the curriculum for the subjects covered by these classes.
     * Falls back to 3 units per subject when not found in curriculum_subjects.
     */
    protected function totalUnitsForCurriculum(array $program, $classes): float
    {
        $curriculumId = $program['curriculum_id'] ?? null;
        if (!$curriculumId || $classes->isEmpty()) {
            return 0.0;
        }

        $subjectIds = $classes->pluck('subject_id')->unique()->all();

        $unitMap = CurriculumSubject::query()
            ->where('curriculum_id', $curriculumId)
            ->whereIn('subject_id', $subjectIds)
            ->pluck('units', 'subject_id');

        return (float) $classes->sum(fn ($c) => (float) ($unitMap[$c->subject_id] ?? 3));
    }

    protected function buildContext(
        Student $student,
        Term $term,
        array $program,
        ?int $sectionId,
        $classIds = null
    ): EnrolmentContext {
        return new EnrolmentContext(
            student:           $student,
            term:              $term,
            programId:         (int) $program['program_id'],
            educationLevel:    $program['education_level'],
            programType:       $program['program_type'] ?? 'regular',
            enrolleeType:      $program['enrollee_type'] ?? 'new',
            selectedClassIds:  $classIds instanceof \Illuminate\Support\Collection ? $classIds : collect($classIds ?? []),
            sectionId:         $sectionId,
            enrollment:        null,
            schoolId:          $student->school_id,
            homeSchoolId:      $student->home_school_id,
        );
    }

    protected function summariseRemarks($result, array $program): ?string
    {
        $bits = [];
        if (!empty($program['curriculum_id']) && !empty($program['year_level'])) {
            $bits[] = "Curriculum #{$program['curriculum_id']} · Y{$program['year_level']}-S{$program['semester']}";
        }
        if ($result->hasWarnings()) {
            $bits[] = 'Warnings: '.collect($result->toArray()['warnings'] ?? [])->pluck('message')->implode('; ');
        }
        return $bits ? implode(' · ', $bits) : null;
    }
}
