<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAcademicBackground;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Modules\AcadEnrolment\Services\ApprovalRouterService;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\EnrolmentValidationPipeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Common skeleton for the Basic Education and Senior High School
 * enrolment wizards. Both flow:
 *
 *   3 — Family & Emergency
 *   4 — Academic Background
 *   5 — Program / Strand / Grade Level
 *   6 — Section preference
 *   7 — Review
 *   submit / confirmation
 *
 * Subclasses set the configuration constants and customise step 5.
 */
abstract class AbstractWizardEnrollmentController extends Controller
{
    /** Short identifier used in session keys + route names ("basic", "shs"). */
    protected string $track;

    /** View namespace, e.g. "acad_enrolment.basic_ed". */
    protected string $viewNs;

    /** Route name prefix, e.g. "public.apply.basic". */
    protected string $routePrefix;

    /** Validators skipped during pipeline run on review/submit. */
    protected array $skip = [
        'prerequisites',
        'schedule_conflict',
        'class_capacity',
        'units_range',
        'payment_gate',
        'subject_in_curriculum',
    ];

    /* ------------------------------------------------------------------ */
    /* Step 3 — Family & Emergency                                        */
    /* ------------------------------------------------------------------ */

    public function showStep3($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();

        return view("{$this->viewNs}.step3_family", [
            'term'             => $term,
            'student'          => $student,
            'parents'          => $student->guardians()->whereIn('type', ['parent', 'guardian'])->get(),
            'emergencyContact' => $student->guardians()->where('is_emergency_contact', true)->first(),
        ]);
    }

    public function storeStep3(Request $request, $termId)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();

        $data = $request->validate([
            'parents'                            => 'nullable|array|max:5',
            'parents.*.relationship'             => 'nullable|string|max:64',
            'parents.*.first_name'               => 'required_with:parents.*.last_name|string|max:255',
            'parents.*.last_name'                => 'required_with:parents.*.first_name|string|max:255',
            'parents.*.middle_name'              => 'nullable|string|max:255',
            'parents.*.occupation'               => 'nullable|string|max:255',
            'parents.*.employer'                 => 'nullable|string|max:255',
            'parents.*.mobile_number'            => 'nullable|string|max:32',
            'parents.*.email'                    => 'nullable|email|max:255',
            'parents.*.is_primary'               => 'nullable|boolean',

            'emergency'                          => 'required|array',
            'emergency.first_name'               => 'required|string|max:255',
            'emergency.last_name'                => 'required|string|max:255',
            'emergency.relationship'             => 'required|string|max:64',
            'emergency.mobile_number'            => 'required|string|max:32',
            'emergency.email'                    => 'nullable|email|max:255',
            'emergency.address'                  => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($student, $data) {
            $student->guardians()->delete();

            foreach (($data['parents'] ?? []) as $i => $p) {
                if (empty($p['first_name']) && empty($p['last_name'])) continue;
                Guardian::create(array_merge($p, [
                    'student_id' => $student->id,
                    'type'       => 'parent',
                    'is_primary' => (bool) ($p['is_primary'] ?? ($i === 0)),
                ]));
            }
            Guardian::create(array_merge($data['emergency'], [
                'student_id'           => $student->id,
                'type'                 => 'emergency',
                'is_emergency_contact' => true,
            ]));
        });

        return redirect()->route("{$this->routePrefix}.step4", $term->id);
    }

    /* ------------------------------------------------------------------ */
    /* Step 4 — Academic Background                                        */
    /* ------------------------------------------------------------------ */

    public function showStep4($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();

        return view("{$this->viewNs}.step4_background", [
            'term'        => $term,
            'student'     => $student,
            'backgrounds' => $student->academicBackgrounds()->orderByDesc('year_ended')->get(),
        ]);
    }

    public function storeStep4(Request $request, $termId)
    {
        $term    = Term::findOrFail($termId);
        $student = $this->requireStudent();

        $data = $request->validate([
            'backgrounds'                      => 'nullable|array|max:5',
            'backgrounds.*.education_level'    => 'required_with:backgrounds.*.school_name|string|max:32',
            'backgrounds.*.school_name'        => 'required_with:backgrounds.*.education_level|string|max:255',
            'backgrounds.*.school_address'     => 'nullable|string|max:500',
            'backgrounds.*.school_type'        => 'nullable|in:public,private,home',
            'backgrounds.*.last_grade_level'   => 'nullable|string|max:32',
            'backgrounds.*.year_started'       => 'nullable|integer|min:1950|max:2100',
            'backgrounds.*.year_ended'         => 'nullable|integer|min:1950|max:2100',
            'backgrounds.*.gpa'                => 'nullable|numeric|min:0|max:100',
            'backgrounds.*.honors'             => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($student, $data) {
            $student->academicBackgrounds()->delete();
            foreach (($data['backgrounds'] ?? []) as $b) {
                if (empty($b['school_name'])) continue;
                StudentAcademicBackground::create(array_merge($b, [
                    'student_id' => $student->id,
                ]));
            }
        });

        // Sidebar progress gating: mark academic background as done.
        session()->put($this->sessionKey($term->id, 'background'), true);

        return redirect()->route("{$this->routePrefix}.step5", $term->id);
    }

    /* ------------------------------------------------------------------ */
    /* Step 5 — Program / Strand / Grade Level (subclass-specific)        */
    /* ------------------------------------------------------------------ */

    abstract public function showStep5($termId);

    abstract public function storeStep5(Request $request, $termId);

    /* ------------------------------------------------------------------ */
    /* Step 6 — Section preference                                         */
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
            ->where('is_active', true)
            ->orderBy('year_level')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'year_level', 'capacity']);

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

        $data = $request->validate(['section_id' => 'nullable|exists:sections,id']);
        session()->put($this->sessionKey($term->id, 'section'), $data['section_id'] ?? null);

        return redirect()->route("{$this->routePrefix}.step7", $term->id);
    }

    /* ------------------------------------------------------------------ */
    /* Step 7 — Review & Submit                                            */
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
        $ctx       = $this->buildContext($student, $term, $program, $sectionId);
        $result    = $pipeline->run($ctx, $this->skip);
        $approval  = $router->route($ctx, null);

        return view("{$this->viewNs}.step7_review", [
            'term'        => $term,
            'student'     => $student,
            'program'     => $program,
            'section'     => $sectionId ? Section::find($sectionId) : null,
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
        $ctx       = $this->buildContext($student, $term, $program, $sectionId);
        $result    = $pipeline->run($ctx, $this->skip);

        if (!$result->passed()) {
            return redirect()->route("{$this->routePrefix}.step7", $term->id)
                ->withErrors(['enrolment' => collect($result->failures())->pluck('message')->all()]);
        }

        $approval = $router->route($ctx, null);

        $enrollment = StudentEnrollment::create([
            'school_id'        => $student->school_id,
            'student_id'       => $student->id,
            'academic_year_id' => $term->academic_year_id,
            'term_id'          => $term->id,
            'program_id'       => $program['program_id'],
            'education_level'  => $program['education_level'],
            'program_type'     => $program['program_type'] ?? 'regular',
            'enrollee_type'    => $program['enrollee_type'] ?? 'new',
            'section_id'       => $sectionId,
            'status'           => StudentEnrollment::STATUS_SUBMITTED,
            'approval_level'   => $approval,
            'remarks'          => $this->summariseRemarks($result, $program),
        ]);

        session()->forget($this->sessionKey($term->id));

        return redirect()->route("{$this->routePrefix}.confirmation", [
            'term'       => $term->id,
            'enrollment' => $enrollment->id,
        ]);
    }

    public function confirmation($termId, $enrollmentId)
    {
        $term       = Term::findOrFail($termId);
        $student    = $this->requireStudent();
        $enrollment = StudentEnrollment::where('student_id', $student->id)->findOrFail($enrollmentId);

        return view("{$this->viewNs}.step8_confirmation", compact('term', 'student', 'enrollment'));
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    protected function requireStudent(): Student
    {
        $student = auth()->user()->student ?? null;
        abort_unless($student, 403, 'Complete Step 1 (personal info) before proceeding.');
        return $student;
    }

    protected function sessionKey($termId, ?string $bucket = null): string
    {
        return $bucket
            ? "apply.{$this->track}.{$termId}.{$bucket}"
            : "apply.{$this->track}.{$termId}";
    }

    protected function programDraft($termId): array
    {
        return (array) session()->get($this->sessionKey($termId, 'program'), []);
    }

    protected function sectionDraft($termId): ?int
    {
        $v = session()->get($this->sessionKey($termId, 'section'));
        return $v ? (int) $v : null;
    }

    protected function summariseRemarks($result, array $program): ?string
    {
        $bits = [];
        if (!empty($program['strand']))   $bits[] = "Strand: {$program['strand']}";
        if ($result->hasWarnings()) {
            $bits[] = 'Warnings: '.collect($result->toArray()['warnings'] ?? [])->pluck('message')->implode('; ');
        }
        return $bits ? implode(' · ', $bits) : null;
    }

    protected function buildContext(Student $student, Term $term, array $program, ?int $sectionId): EnrolmentContext
    {
        return new EnrolmentContext(
            student:           $student,
            term:              $term,
            programId:         (int) $program['program_id'],
            educationLevel:    $program['education_level'],
            programType:       $program['program_type'] ?? 'regular',
            enrolleeType:      $program['enrollee_type'] ?? 'new',
            selectedClassIds:  collect(),
            sectionId:         $sectionId,
            enrollment:        null,
            schoolId:          $student->school_id,
            homeSchoolId:      $student->home_school_id,
        );
    }
}
