<?php

namespace App\Http\Controllers\Public;

use App\Models\ClassModel;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentEnrollmentSubject;
use App\Models\Term;
use App\Modules\AcadEnrolment\Services\ApprovalRouterService;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\EnrolmentValidationPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Higher Education **Irregular** enrolment wizard.
 *
 * Flow:
 *   3 — Family
 *   4 — Academic Background
 *   5 — Program + Curriculum (year/sem optional, used only as a filter hint)
 *   6 — Per-class picker with **live AJAX validation**
 *   7 — Review (re-runs full pipeline server-side)
 *
 * Live validation endpoint: POST /apply/{term}/higher-irregular/validate
 * returns the raw pipeline issues, total units, and computed approval level.
 */
class HigherEdIrregularEnrollmentController extends AbstractWizardEnrollmentController
{
    protected string $track       = 'higher_irregular';
    protected string $viewNs      = 'acad_enrolment.higher_ed_irregular';
    protected string $routePrefix = 'public.apply.higher_irregular';

    /** Run the full pipeline; payment_gate only kicks in after billing. */
    protected array $skip = ['payment_gate'];

    /* ------------------------------------------------------------------ */
    /* Step 5 — Program + Curriculum (year/sem are filter hints, not hard) */
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

        $curricula = Curriculum::query()
            ->where('school_id', $student->school_id)
            ->where('is_active', true)
            ->orderBy('program_id')->orderBy('version')
            ->get(['id', 'program_id', 'name', 'version']);

        return view("{$this->viewNs}.step5_curriculum", [
            'term'      => $term,
            'student'   => $student,
            'programs'  => $programs,
            'curricula' => $curricula,
            'draft'     => $this->programDraft($term->id),
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
            'year_level'      => 'nullable|integer|min:1|max:6',
            'semester'        => 'nullable|in:1,2,3',
            'education_level' => 'required|in:undergraduate,graduate',
            'enrollee_type'   => 'required|in:regular,irregular,transferee,returnee,cross_enrollee,special',
            'program_type'    => 'required|in:regular,bridging,non_degree',
        ]);

        // Curriculum is no longer student-selected — auto-resolve the latest
        // active curriculum for this program. Downstream class filtering and
        // unit totals depend on this id.
        $data['curriculum_id'] = Curriculum::query()
            ->where('program_id', $data['program_id'])
            ->where('is_active', true)
            ->orderByDesc('version')
            ->value('id');

        session()->put($this->sessionKey($term->id, 'program'), $data);
        session()->forget($this->sessionKey($term->id, 'classes'));

        return redirect()->route("{$this->routePrefix}.step6", $term->id);
    }

    /* ------------------------------------------------------------------ */
    /* Step 6 — Class picker                                               */
    /* ------------------------------------------------------------------ */

    public function showStep6($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();
        $draft   = $this->programDraft($term->id);

        if (empty($draft['program_id'])) {
            return redirect()->route("{$this->routePrefix}.step5", $term->id);
        }


        // Show ALL open classes for the selected term and school (not just curriculum subjects)
        $classes = ClassModel::query()
            ->with(['subject:id,name,code', 'section:id,name'])
            ->where('school_id', $student->school_id)
            ->where('term_id', $term->id)
            ->where('is_open', true)
            ->withCount('students')
            ->orderBy('section_id')
            ->orderBy('subject_id')
            ->orderBy('code')
            ->get(['id', 'subject_id', 'code', 'room', 'schedule', 'capacity', 'section_id', 'teacher_id']);

        // Optionally, still load curriculum subjects for warnings/highlighting
        $curriculumSubjects = empty($draft['curriculum_id'])
            ? collect()
            : CurriculumSubject::query()
                ->with('subject:id,name,code')
                ->where('curriculum_id', $draft['curriculum_id'])
                ->get();
        $meta = $curriculumSubjects->keyBy('subject_id');

        $picked = collect((array) session($this->sessionKey($term->id, 'classes'), []))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        return view("{$this->viewNs}.step6_classes", [
            'term'      => $term,
            'student'   => $student,
            'draft'     => $draft,
            'classes'   => $classes,
            'meta'      => $meta,
            'picked'    => $picked,
            'validateUrl' => route("{$this->routePrefix}.validate", $term->id),
        ]);
    }

    public function storeStep6(Request $request, $termId)
    {
        $term = Term::findOrFail($termId);
        $this->requireStudent();

        $data = $request->validate([
            'class_ids'   => 'required|array|min:1',
            'class_ids.*' => 'integer|exists:classes,id',
        ]);

        session()->put($this->sessionKey($term->id, 'classes'), array_values(array_unique($data['class_ids'])));

        return redirect()->route("{$this->routePrefix}.step7", $term->id);
    }

    /* ------------------------------------------------------------------ */
    /* Live validation API                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * AJAX endpoint used by Step 6 — runs the pipeline against the
     * tentative class selection without persisting anything.
     */
    public function validateSelection(
        $termId,
        Request $request,
        EnrolmentValidationPipeline $pipeline,
        ApprovalRouterService $router
    ): JsonResponse {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();
        $program = $this->programDraft($term->id);

        if (empty($program['program_id'])) {
            return response()->json(['error' => 'Complete Step 5 first.'], 422);
        }

        $data = $request->validate([
            'class_ids'   => 'array',
            'class_ids.*' => 'integer|exists:classes,id',
        ]);
        $classIds = collect($data['class_ids'] ?? [])->map(fn ($v) => (int) $v)->unique()->values();

        $totalUnits = $this->totalUnitsForCurriculum($program, $classIds);

        $ctx    = $this->buildContext($student, $term, $program, null, $classIds);
        $result = $pipeline->run($ctx, $this->skip);
        $approval = $router->route($ctx, (float) $totalUnits);

        return response()->json([
            'passed'       => $result->passed(),
            'has_warnings' => $result->hasWarnings(),
            'failures'     => $result->failures(),
            'warnings'     => array_values(array_filter(
                $result->issues,
                fn ($i) => $i['severity'] === 'warn'
            )),
            'total_units'  => $totalUnits,
            'class_count'  => $classIds->count(),
            'approval'     => $approval,
        ]);
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

        $classIds = collect((array) session($this->sessionKey($term->id, 'classes'), []))
            ->map(fn ($v) => (int) $v)->unique()->values();

        if ($classIds->isEmpty()) {
            return redirect()->route("{$this->routePrefix}.step6", $term->id);
        }

        $classes = ClassModel::query()
            ->with('subject:id,name,code')
            ->whereIn('id', $classIds)
            ->orderBy('code')
            ->get(['id', 'subject_id', 'code', 'room', 'schedule', 'capacity']);

        $totalUnits = $this->totalUnitsForCurriculum($program, $classIds);

        $ctx      = $this->buildContext($student, $term, $program, null, $classIds);
        $result   = $pipeline->run($ctx, $this->skip);
        $approval = $router->route($ctx, (float) $totalUnits);

        return view("{$this->viewNs}.step7_review", [
            'term'        => $term,
            'student'     => $student,
            'program'     => $program,
            'classes'     => $classes,
            'totalUnits'  => $totalUnits,
            'parents'     => $student->guardians()->whereIn('type', ['parent', 'guardian'])->get(),
            'emergency'   => $student->guardians()->where('is_emergency_contact', true)->first(),
            'backgrounds' => $student->academicBackgrounds()->orderByDesc('year_ended')->get(),
            'result'      => $result,
            'approval'    => $approval,
        ]);
    }

    public function submit(
        $termId,
        Request $request,
        EnrolmentValidationPipeline $pipeline,
        ApprovalRouterService $router
    ) {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();
        $program = $this->programDraft($term->id);

        if (empty($program['program_id'])) {
            return redirect()->route("{$this->routePrefix}.step5", $term->id);
        }

        $classIds = collect((array) session($this->sessionKey($term->id, 'classes'), []))
            ->map(fn ($v) => (int) $v)->unique()->values();

        if ($classIds->isEmpty()) {
            return redirect()->route("{$this->routePrefix}.step6", $term->id);
        }

        $classes = ClassModel::query()
            ->whereIn('id', $classIds)
            ->get(['id', 'subject_id']);

        $totalUnits = $this->totalUnitsForCurriculum($program, $classIds);

        $ctx    = $this->buildContext($student, $term, $program, null, $classIds);
        $result = $pipeline->run($ctx, $this->skip);

        if (!$result->passed()) {
            return redirect()->route("{$this->routePrefix}.step7", $term->id)
                ->withErrors(['enrolment' => collect($result->failures())->pluck('message')->all()]);
        }

        $approval = $router->route($ctx, (float) $totalUnits);

        $enrollment = DB::transaction(function () use ($student, $term, $program, $classes, $totalUnits, $approval, $result) {
            $en = StudentEnrollment::create([
                'school_id'        => $student->school_id,
                'student_id'       => $student->id,
                'academic_year_id' => $term->academic_year_id,
                'term_id'          => $term->id,
                'program_id'       => $program['program_id'],
                'education_level'  => $program['education_level'],
                'program_type'     => $program['program_type'] ?? 'regular',
                'enrollee_type'    => $program['enrollee_type'] ?? 'irregular',
                'section_id'       => null,
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
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    protected function totalUnitsForCurriculum(array $program, $classIds): float
    {
        $curriculumId = $program['curriculum_id'] ?? null;
        if (!$curriculumId || $classIds->isEmpty()) return 0.0;

        $classes = ClassModel::whereIn('id', $classIds)->get(['id', 'subject_id']);
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
            enrolleeType:      $program['enrollee_type'] ?? 'irregular',
            selectedClassIds:  $classIds instanceof \Illuminate\Support\Collection ? $classIds : collect($classIds ?? []),
            sectionId:         $sectionId,
            enrollment:        null,
            schoolId:          $student->school_id,
            homeSchoolId:      $student->home_school_id,
        );
    }

    protected function summariseRemarks($result, array $program): ?string
    {
        $bits = ['Irregular enrolment'];
        if (!empty($program['curriculum_id'])) {
            $bits[] = "Curriculum #{$program['curriculum_id']}";
        }
        if ($result->hasWarnings()) {
            $msgs = collect($result->issues)
                ->where('severity', 'warn')
                ->pluck('message')
                ->implode('; ');
            if ($msgs) $bits[] = 'Warnings: '.$msgs;
        }
        return implode(' · ', $bits);
    }
}
