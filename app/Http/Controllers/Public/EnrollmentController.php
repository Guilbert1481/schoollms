<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Guardian;
use App\Models\Term;
use App\Models\EducationNode;
use App\Models\EnrollmentDraft;
use App\Models\Modality;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentAcademicBackground;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\EnrollmentSubmittedMail;

class EnrollmentController extends Controller
{
    /**
     * Show the enrollment form (Step 1).
     */
    public function show($term)
    {
        $term = Term::findOrFail($term);

        // Re-hydrate session from a saved draft so a student who clicked
        // "Back to Dashboard" mid-enrolment can pick up where they left off.
        $this->hydrateDraftIntoSession($term->id);

        return view('public.enrollment_form', compact('term'));
    }

    /**
     * Snapshot every `apply.*.{termId}` session key into the
     * enrollment_drafts table and send the student to their dashboard.
     */
    public function exitToDashboard($termId)
    {
        $term = Term::findOrFail($termId);

        $student = auth()->user()?->student;
        if (!$student) {
            return redirect()->route('student.dashboard');
        }

        $snapshot = [];
        foreach (session()->all() as $key => $value) {
            if (str_starts_with($key, 'apply.') && str_ends_with($key, ".{$term->id}")) {
                $snapshot[$key] = $value;
            }
            // Some keys are nested under `apply.{track}.{termId}.*`.
            if (preg_match("/^apply\\..+\\.{$term->id}\\..+$/", $key)) {
                $snapshot[$key] = $value;
            }
        }

        EnrollmentDraft::updateOrCreate(
            ['student_id' => $student->id, 'term_id' => $term->id],
            ['data' => json_encode($snapshot)]
        );

        return redirect()->route('student.dashboard')
            ->with('status', 'Your progress has been saved. You can resume your enrolment any time.');
    }

    /**
     * Restore session keys from a previously-saved EnrollmentDraft so the
     * wizard sidebar reflects the student's prior progress on return.
     */
    protected function hydrateDraftIntoSession(int $termId): void
    {
        $student = auth()->user()?->student;
        if (!$student) return;

        $draft = EnrollmentDraft::where('student_id', $student->id)
            ->where('term_id', $termId)
            ->first();
        if (!$draft || empty($draft->data)) return;

        $data = is_array($draft->data) ? $draft->data : json_decode($draft->data, true);
        if (!is_array($data)) return;

        foreach ($data as $key => $value) {
            // Don't clobber anything the user has set in the current session.
            if (!session()->has($key)) {
                session()->put($key, $value);
            }
        }
    }

    /**
     * Generate a unique student_number, e.g. "S-2026-000123".
     */
    protected function generateStudentNumber(): string
    {
        $year = now()->format('Y');
        do {
            $candidate = sprintf('S-%s-%06d', $year, random_int(1, 999999));
        } while (Student::where('student_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Save Step 1 (Personal Info) and move to next step.
     */
    public function store(Request $request, $termId)
    {
        if (!auth()->check()) {
            return $request->ajax()
                ? response()->json(['error' => 'Session expired.'], 401)
                : redirect()->route('login');
        }

        $validated = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'gender'       => 'required|string',
            'dob'          => 'required|date',
            'nationality'  => 'required|string',
            'civil_status' => 'required|string',

            'middle_name'          => 'nullable|string|max:255',
            'preferred_name'       => 'nullable|string|max:255',
            'religion'             => 'nullable|string|max:64',
            'religion_subcategories'   => 'nullable|array',
            'religion_subcategories.*' => 'string|max:64',
            'government_id_type'   => 'nullable|string',
            'government_id_number' => 'nullable|string',
        ]);

        $user = auth()->user();
        // Reload the relation so we always see fresh DB state. Combined with
        // firstOrCreate below this guarantees one row per user.
        $student = $user->student()->first();

        if (!$student) {
            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id'      => $user->school_id ?? $request->school_id ?? 1,
                    'student_number' => $this->generateStudentNumber(),
                    'first_name'     => $validated['first_name'],
                    'last_name'      => $validated['last_name'],
                    'email'          => $user->email,
                ]
            );
        }

        if ($request->hasFile('student_photo') && blank($student->photo_path)) {
            $student->photo_path = $request->file('student_photo')
                ->store('profile_photos', 'public');
        }

        if ($request->hasFile('id_file') && blank($student->photo_id)) {
            $student->photo_id = $request->file('id_file')
                ->store('id_documents', 'public');
        }

        // Only populate columns that are currently empty — never overwrite
        // data the student (or admin) has already filled in.
        $incoming = [
            'first_name'           => $validated['first_name'],
            'middle_name'          => $request->middle_name,
            'last_name'            => $validated['last_name'],
            'preferred_name'       => $request->preferred_name,
            'gender'               => $validated['gender'],
            'sexual_orientation'   => $request->sexual_orientation,
            'date_of_birth'        => $validated['dob'],
            'nationality'          => $validated['nationality'],
            'civil_status'         => $validated['civil_status'],
            'government_id_type'   => $request->government_id_type,
            'government_id_number' => $request->government_id_number,
        ];

        foreach ($incoming as $column => $value) {
            if (blank($student->{$column}) && filled($value)) {
                $student->{$column} = $value;
            }
        }

        // Religion: when "Christian" is chosen, store the picked denomination
        // (e.g. "Catholic") directly in the `religion` column so we don't need
        // a separate subcategory column. For all other religions, store the
        // top-level value as-is.
        $religion = $request->input('religion');
        if (filled($religion)) {
            if ($religion === 'Christian') {
                $subs = $request->input('religion_subcategories', []);
                if (is_array($subs)) {
                    $subs = array_values(array_filter($subs));
                }
                $student->religion = (is_array($subs) && count($subs) > 0)
                    ? $subs[0]
                    : 'Christian';
            } else {
                $student->religion = $religion;
            }
        }

        $student->save();

        if ($request->ajax()) {
            return response()->json([
                'success'  => true,
                'next_url' => route('public.apply.step2', $termId)
            ]);
        }

        return redirect()->route('public.apply.step2', $termId);
    }

    /**
     * Save Draft
     */
    public function saveDraft(Request $request, $termId)
    {
        if (!Auth::check() || !Auth::user()->student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $student = Auth::user()->student;

        EnrollmentDraft::updateOrCreate(
            [
                'student_id'  => $student->id,
                'term_id' => $termId,
            ],
            [
                'data' => json_encode($request->all())
            ]
        );

        return response()->json(['status' => 'saved']);
    }

    /**
     * Show Step 2
     */
    public function showStep2($termId)
    {
        $term = Term::findOrFail($termId);
        $student  = auth()->user()->student;

        return view('public.contact_details', compact('term', 'student'));
    }

    /**
     * Save Step 2 (Contact Details) and move to next step.
     */
    public function storeStep2(Request $request, $termId)
    {
        $term = Term::findOrFail($termId);

        $isPh = strtoupper((string) $request->input('country')) === 'PH';

        $validated = $request->validate([
            'mobile_number'     => 'required|string|max:32',
            'email'             => 'required|email|max:191',

            'country'           => 'required|string|size:2',
            'country_code'      => 'nullable|string|max:8',
            'region'            => 'required|string|max:128',
            // Province is conceptually required for most PH regions, but NCR
            // (and a few others) have no provinces. The React form enforces
            // province selection only when provinces exist for the region, so
            // we keep the server side permissive.
            'province'          => 'nullable|string|max:128',
            'city_municipality' => 'required|string|max:128',
            'barangay'          => $isPh ? 'required|string|max:128' : 'nullable|string|max:128',
            'zip_code'          => 'required|string|max:16',
            'address_line_1'    => 'required|string|max:255',
            'address_line_2'    => 'nullable|string|max:255',
        ]);

        $student = auth()->user()->student;
        if (!$student) {
            return redirect()
                ->route('public.apply.show', $term->id)
                ->withErrors(['student' => 'Please complete Step 1 first.']);
        }

        $student->update($validated);

        return redirect()->route('public.apply.family', $term->id)
            ->with('status', 'Contact details saved.');
    }

    /**
     * Step 3 — Family & Emergency. Track-agnostic so it can run before the
     * Track Picker. Guardians are saved straight to the DB; a session flag is
     * also written so the sidebar's progress gating can mark this step done.
     */
    public function showFamily($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = auth()->user()->student;

        if (! $student) {
            return redirect()->route('public.apply.show', $term->id)
                ->withErrors(['student' => 'Please complete Step 1 first.']);
        }

        return view('acad_enrolment.shared.family_page', [
            'term'             => $term,
            'student'          => $student,
            'parents'          => $student->guardians()->whereIn('type', ['parent', 'guardian'])->get(),
            'emergencyContact' => $student->guardians()->where('is_emergency_contact', true)->first(),
        ]);
    }

    public function storeFamily(Request $request, $termId)
    {
        $term    = Term::findOrFail($termId);
        $student = auth()->user()->student;

        if (! $student) {
            return redirect()->route('public.apply.show', $term->id)
                ->withErrors(['student' => 'Please complete Step 1 first.']);
        }

        $data = $request->validate([
            'parents'                  => 'nullable|array|max:5',
            'parents.*.relationship'   => 'nullable|string|max:64',
            'parents.*.first_name'     => 'required_with:parents.*.last_name|string|max:255',
            'parents.*.last_name'      => 'required_with:parents.*.first_name|string|max:255',
            'parents.*.middle_name'    => 'nullable|string|max:255',
            'parents.*.occupation'     => 'nullable|string|max:255',
            'parents.*.employer'       => 'nullable|string|max:255',
            'parents.*.mobile_number'  => 'nullable|string|max:32',
            'parents.*.email'          => 'nullable|email|max:255',
            'parents.*.is_primary'     => 'nullable|boolean',

            'emergency'                => 'required|array',
            'emergency.first_name'     => 'required|string|max:255',
            'emergency.last_name'      => 'required|string|max:255',
            'emergency.relationship'   => 'required|string|max:64',
            'emergency.mobile_number'  => 'required|string|max:32',
            'emergency.email'          => 'nullable|email|max:255',
            'emergency.address'        => 'nullable|string|max:500',
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

        // Sidebar progress gating: mark family step as done.
        session()->put("apply.family.{$term->id}", true);

        return redirect()->route('public.apply.pathway', $term->id)
            ->with('status', 'Family details saved.');
    }

    /* =================================================================
     |  STEP 4 — LEARNING PATHWAY
     |  Educational Level + cascading nodes → Program + Year Level +
     |  Modality + Student Type. Single-page form.
     | =================================================================*/

    public function showPathway($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = auth()->user()->student;

        if (! $student) {
            return redirect()->route('public.apply.show', $term->id)
                ->withErrors(['student' => 'Please complete Step 1 first.']);
        }

        // Top-level (root) educational levels — these are the entry points
        // (Basic Ed, Senior High, College, Post-Bac, Masteral, Doctoral, etc.).
        $rootLevels = EducationNode::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->where('is_offered', true)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get(['id', 'name', 'node_type']);

        $modalities = Modality::orderBy('id')->get(['id', 'name', 'code']);

        // Load saved pathway: DB draft is authoritative; fall back to session
        // (older flows may have stored to session only).
        $draft = EnrollmentDraft::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->first();

        $saved = $draft?->data['pathway']
            ?? session("apply.pathway.{$term->id}", []);

        // Reconstruct the cascade chain (root → … → leaf) so the JS can
        // replay the dropdowns and pre-select what the student picked before.
        $savedChain = [];
        if (! empty($saved['education_node_id'])) {
            $cursor = EducationNode::find($saved['education_node_id']);
            while ($cursor) {
                array_unshift($savedChain, [
                    'id'   => $cursor->id,
                    'name' => $cursor->name,
                    'type' => $cursor->node_type,
                ]);
                $cursor = $cursor->parent_id ? EducationNode::find($cursor->parent_id) : null;
            }
        }

        return view('acad_enrolment.shared.pathway', [
            'term'        => $term,
            'student'     => $student,
            'rootLevels'  => $rootLevels,
            'modalities'  => $modalities,
            'saved'       => $saved,
            'savedChain'  => $savedChain,
        ]);
    }

    /**
     * AJAX — return the children + programs of an education node so the
     * pathway picker can drill down until a Program is reached.
     */
    public function pathwayBranch($termId, $nodeId)
    {
        $node = EducationNode::with('children')->findOrFail($nodeId);

        $children = $node->children
            ->where('is_active', true)
            ->where('is_offered', true)
            ->values()
            ->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->name,
                'type' => $c->node_type,
            ]);

        $student   = auth()->user()->student;
        $schoolId  = $student?->school_id;

        $programs = Program::query()
            ->where('education_node_id', $node->id)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'node'     => ['id' => $node->id, 'name' => $node->name, 'type' => $node->node_type],
            'children' => $children,
            'programs' => $programs,
        ]);
    }

    /**
     * AJAX — return the active curriculum's subjects for a programme so the
     * pathway page can preview a student's class load on the right panel.
     *
     * Response shape:
     *   {
     *     curriculum: {id, name, version} | null,
     *     current_year_subjects:    [...],   // matching year_level (with sem grouping)
     *     additional_subjects:      [...],   // other year_levels in same curriculum
     *     mode: 'preview'|'pickable'         // pickable when student_type ∈ transferee|returnee|irregular
     *   }
     */
    public function pathwaySubjects(Request $request, $termId)
    {
        $data = $request->validate([
            'program_id'   => 'required|integer|exists:programs,id',
            'year_level'   => 'nullable|integer|min:1|max:6',
            'student_type' => 'nullable|in:new,transferee,returnee,regular,irregular',
        ]);

        $programId   = (int) $data['program_id'];
        $yearLevel   = $data['year_level'] ?? null;
        $studentType = $data['student_type'] ?? null;

        $pickable = in_array($studentType, ['transferee', 'returnee', 'irregular'], true);

        // Active curriculum for this programme (newest active wins).
        $curriculum = Curriculum::where('program_id', $programId)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (! $curriculum) {
            return response()->json([
                'curriculum'            => null,
                'current_year_subjects' => [],
                'additional_subjects'   => [],
                'mode'                  => $pickable ? 'pickable' : 'preview',
            ]);
        }

        $rows = CurriculumSubject::where('curriculum_id', $curriculum->id)
            ->with('subject:id,code,name,is_active')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->get()
            ->filter(fn ($r) => $r->subject && $r->subject->is_active)
            ->values();

        $shape = fn ($r) => [
            'id'          => $r->id,
            'subject_id'  => $r->subject_id,
            'code'        => $r->subject->code,
            'name'        => $r->subject->name,
            'units'       => $r->units,
            'year_level'  => (int) $r->year_level,
            'semester'    => (int) $r->semester,
            'is_core'     => (bool) $r->is_core,
            'is_elective' => (bool) $r->is_elective,
        ];

        $current    = $yearLevel
            ? $rows->where('year_level', (int) $yearLevel)->values()
            : $rows;
        $additional = $yearLevel
            ? $rows->where('year_level', '!=', (int) $yearLevel)->values()
            : collect();

        return response()->json([
            'curriculum' => [
                'id'      => $curriculum->id,
                'name'    => $curriculum->name,
                'version' => $curriculum->version,
            ],
            'current_year_subjects' => $current->map($shape)->all(),
            'additional_subjects'   => $additional->map($shape)->all(),
            'mode'                  => $pickable ? 'pickable' : 'preview',
        ]);
    }

    public function storePathway(Request $request, $termId)
    {
        $term = Term::findOrFail($termId);

        $modalityCode = optional(Modality::find($request->input('modality_id')))->code;
        $isAsync      = $modalityCode === 'async_online';

        // Determine whether the selected education node has any programs.
        // Basic-ed leaf nodes (e.g. "Grade 2") have no programs, so program_id
        // is optional there. Higher-ed and SHS strands always have programs.
        $nodeId = $request->input('education_node_id');
        $nodeHasPrograms = $nodeId
            ? \App\Models\Program::where('education_node_id', $nodeId)->exists()
            : false;

        $rules = [
            'education_node_id' => 'required|integer|exists:education_nodes,id',
            'program_id'        => ($nodeHasPrograms ? 'required|' : 'nullable|') . 'integer|exists:programs,id',
            'year_level'        => 'nullable|integer|min:1|max:12',
            'modality_id'       => 'required|integer|exists:modalities,id',
            'picked_subjects'   => 'nullable|array',
            'picked_subjects.*' => 'integer|exists:subjects,id',
        ];

        // Student Type only applies when modality is NOT Asynchronous Online.
        if (! $isAsync) {
            $rules['student_type'] = 'required|in:new,transferee,returnee,regular,irregular';
        }

        $data = $request->validate($rules);

        if ($isAsync) {
            // Async self-paced learners are treated as a special intake — no
            // student-type designation needed (they don't follow a cohort).
            $data['student_type'] = null;
        }

        session()->put("apply.pathway.{$term->id}", $data);

        // Persist to DB so the wizard survives session expiry / logout.
        $student = auth()->user()->student;
        if ($student) {
            $draft = EnrollmentDraft::firstOrNew([
                'student_id' => $student->id,
                'term_id'    => $term->id,
            ]);
            $existing = $draft->data ?? [];
            $existing['pathway'] = $data;
            $draft->data = $existing;
            $draft->save();
        }

        // Convenience flag for sidebar progress gating.
        session()->put("apply.pathway_done.{$term->id}", true);

        // If student is "regular", they already have an enrolment history with
        // us so Academic Background is skipped — jump straight to Review.
        $studentType = $data['student_type'] ?? null;
        if ($studentType === 'regular') {
            session()->put("apply.academic_skipped.{$term->id}", true);
            return redirect()->route('public.apply.review', $term->id)
                ->with('status', 'Pathway saved. Academic Background skipped for regular students.');
        }

        session()->forget("apply.academic_skipped.{$term->id}");

        return redirect()->route('public.apply.academic', $term->id)
            ->with('status', 'Pathway saved.');
    }

    /* =================================================================
     |  STEP 5 — ACADEMIC BACKGROUND
     |  Skipped automatically when student_type === 'regular'.
     | =================================================================*/

    public function showAcademic($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = auth()->user()->student;

        if (! $student) {
            return redirect()->route('public.apply.show', $term->id);
        }

        // Skip outright if previous step marked it as skipped.
        if (session("apply.academic_skipped.{$term->id}")) {
            return redirect()->route('public.apply.review', $term->id);
        }

        $backgrounds = StudentAcademicBackground::where('student_id', $student->id)
            ->orderBy('year_ended', 'desc')
            ->get();

        // Root-level (offered) education nodes for the cascade picker — same
        // source as the Learning Pathway step.
        $rootLevels = EducationNode::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->where('is_offered', true)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get(['id', 'name', 'node_type']);

        // For each saved background, reconstruct the root→leaf chain so the
        // cascade JS can replay each level's dropdown and pre-select.
        $hydrated = $backgrounds->map(function ($b) {
            $chain  = [];
            $cursor = $b->education_node_id ? EducationNode::find($b->education_node_id) : null;
            while ($cursor) {
                array_unshift($chain, ['id' => $cursor->id, 'name' => $cursor->name]);
                $cursor = $cursor->parent_id ? EducationNode::find($cursor->parent_id) : null;
            }
            return [
                'education_level'   => $b->education_level,
                'education_node_id' => $b->education_node_id,
                'chain'             => $chain,
                'school_name'       => $b->school_name,
                'school_address'    => $b->school_address,
                'school_type'       => $b->school_type,
                'last_grade_level'  => $b->last_grade_level,
                'year_started'      => $b->year_started,
                'year_ended'        => $b->year_ended,
                'gpa'               => $b->gpa,
                'honors'            => $b->honors,
            ];
        })->values()->all();

        return view('acad_enrolment.shared.academic_background', [
            'term'        => $term,
            'student'     => $student,
            'backgrounds' => $backgrounds,
            'hydrated'    => $hydrated,
            'rootLevels'  => $rootLevels,
        ]);
    }

    public function storeAcademic(Request $request, $termId)
    {
        $term    = Term::findOrFail($termId);
        $student = auth()->user()->student;

        $data = $request->validate([
            'backgrounds'                       => 'required|array|min:1',
            'backgrounds.*.education_node_id'   => 'nullable|integer|exists:education_nodes,id',
            'backgrounds.*.education_level'     => 'nullable|string|max:64',
            'backgrounds.*.school_name'         => 'required|string|max:255',
            'backgrounds.*.school_address'      => 'nullable|string|max:255',
            'backgrounds.*.school_type'         => 'nullable|in:public,private,home',
            'backgrounds.*.last_grade_level'    => 'nullable|string|max:64',
            'backgrounds.*.year_started'        => 'nullable|integer|min:1950|max:2100',
            'backgrounds.*.year_ended'          => 'nullable|integer|min:1950|max:2100',
            'backgrounds.*.gpa'                 => 'nullable|numeric|min:0|max:100',
            'backgrounds.*.honors'              => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($student, $data) {
            $student->academicBackgrounds()->delete();
            foreach ($data['backgrounds'] as $bg) {
                // Derive education_level from the root of the chain if not
                // supplied (so legacy reports keep working).
                if (empty($bg['education_level']) && ! empty($bg['education_node_id'])) {
                    $cursor = EducationNode::find($bg['education_node_id']);
                    while ($cursor && $cursor->parent_id) {
                        $cursor = EducationNode::find($cursor->parent_id);
                    }
                    if ($cursor) {
                        $bg['education_level'] = \Illuminate\Support\Str::slug($cursor->name, '_');
                    }
                }
                if (empty($bg['education_level'])) {
                    $bg['education_level'] = 'other';
                }

                StudentAcademicBackground::create(array_merge($bg, [
                    'student_id' => $student->id,
                    'is_current' => false,
                ]));
            }
        });

        session()->put("apply.academic_done.{$term->id}", true);

        return redirect()->route('public.apply.review', $term->id)
            ->with('status', 'Academic background saved.');
    }

    /* =================================================================
     |  STEP 6 — REVIEW & SUBMIT
     | =================================================================*/

    public function showReview($termId)
    {
        $term    = Term::findOrFail($termId);
        $student = auth()->user()->student;

        if (! $student) {
            return redirect()->route('public.apply.show', $term->id);
        }

        // Prefer DB draft over session (survives logout/session expiry).
        $draft   = EnrollmentDraft::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->first();

        $pathway = $draft?->data['pathway']
            ?? session("apply.pathway.{$term->id}", []);
        $skipped = (bool) session("apply.academic_skipped.{$term->id}");

        $program     = !empty($pathway['program_id'])        ? Program::find($pathway['program_id'])           : null;
        $modality    = !empty($pathway['modality_id'])       ? Modality::find($pathway['modality_id'])         : null;
        $node        = !empty($pathway['education_node_id']) ? EducationNode::find($pathway['education_node_id']) : null;

        // Build cascade chain (root → leaf) so we can show "Education Level"
        // as the top-level (e.g. "Senior High School") and the path under it.
        $chain = [];
        $cursor = $node;
        while ($cursor) {
            array_unshift($chain, $cursor);
            $cursor = $cursor->parent_id ? EducationNode::find($cursor->parent_id) : null;
        }
        $rootLevel = $chain[0] ?? null;
        $pathLabel = collect($chain)->skip(1)->pluck('name')->implode(' › ');

        $backgrounds      = $skipped ? collect() : $student->academicBackgrounds()->get();
        $parents          = $student->guardians()->whereIn('type', ['parent', 'guardian'])->get();
        $emergencyContact = $student->guardians()->where('is_emergency_contact', true)->first();

        return view('acad_enrolment.shared.review', compact(
            'term', 'student', 'pathway', 'program', 'modality', 'node',
            'rootLevel', 'pathLabel', 'chain',
            'backgrounds', 'skipped', 'parents', 'emergencyContact'
        ));
    }

    public function submit(Request $request, $termId)
    {
        $term    = Term::findOrFail($termId);
        $student = auth()->user()->student;

        if (! $student) {
            return redirect()->route('public.apply.show', $term->id);
        }

        $pathway = session("apply.pathway.{$term->id}", []);
        if (empty($pathway['program_id'])) {
            return redirect()->route('public.apply.pathway', $term->id)
                ->withErrors(['pathway' => 'Please complete the Learning Pathway step.']);
        }

        $enrollment = DB::transaction(function () use ($term, $student, $pathway) {
            $enrollment = StudentEnrollment::create([
                'school_id'         => $student->school_id,
                'student_id'        => $student->id,
                'academic_year_id'  => $term->academic_year_id,
                'term_id'           => $term->id,
                'program_id'        => $pathway['program_id'],
                'modality_id'       => $pathway['modality_id'] ?? null,
                'education_node_id' => $pathway['education_node_id'] ?? null,
                'year_level'        => $pathway['year_level'] ?? null,
                'student_type'      => $pathway['student_type'] ?? null,
                // Map student_type → enrollee_type for backward-compat with
                // the existing approval / billing pipeline.
                'enrollee_type'     => match ($pathway['student_type'] ?? null) {
                    'new'        => 'new',
                    'transferee' => 'transferee',
                    'returnee'   => 'returnee',
                    'regular'    => 'continuing',
                    'irregular'  => 'irregular',
                    default      => 'special',
                },
                'status'            => StudentEnrollment::STATUS_SUBMITTED,
            ]);

            // Persist picked subjects (only meaningful for transferee/returnee/irregular,
            // but harmless for others — they'll just have an empty array).
            $picked = array_unique(array_map('intval', $pathway['picked_subjects'] ?? []));
            foreach ($picked as $subjectId) {
                \App\Models\StudentEnrollmentSubject::create([
                    'student_enrollment_id' => $enrollment->id,
                    'subject_id'            => $subjectId,
                    'status'                => \App\Models\StudentEnrollmentSubject::STATUS_ENROLLED,
                ]);
            }

            return $enrollment;
        });

        // Wipe wizard session for this term.
        foreach (array_keys(session()->all()) as $key) {
            if (str_starts_with($key, 'apply.') && str_contains($key, ".{$term->id}")) {
                session()->forget($key);
            }
        }

        // ---- Generate PDF & email guardians/emergency contact ----
        try {
            $this->generateAndMailEnrollmentPdf($enrollment, $student, $term, $pathway);
        } catch (\Throwable $e) {
            Log::error('Enrolment PDF/mail failed', [
                'enrollment_id' => $enrollment->id,
                'error'         => $e->getMessage(),
            ]);
        }

        return redirect()->route('public.apply.confirmation', [
            'term'       => $term->id,
            'enrollment' => $enrollment->id,
        ]);
    }

    /**
     * Build the application PDF (A4) and send it as an attachment to every
     * guardian/parent and the emergency contact with a valid email address.
     */
    protected function generateAndMailEnrollmentPdf(
        StudentEnrollment $enrollment,
        Student $student,
        Term $term,
        array $pathway,
    ): void {
        // Re-load draft so we get the canonical pathway data even if session
        // was already cleared above.
        $draft   = EnrollmentDraft::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->first();
        $pathway = $draft?->data['pathway'] ?? $pathway;

        $program  = !empty($pathway['program_id'])        ? Program::find($pathway['program_id'])           : null;
        $modality = !empty($pathway['modality_id'])       ? Modality::find($pathway['modality_id'])         : null;
        $node     = !empty($pathway['education_node_id']) ? EducationNode::find($pathway['education_node_id']) : null;
        $skipped  = ($pathway['student_type'] ?? null) === 'regular';

        $backgrounds      = $skipped
            ? collect()
            : StudentAcademicBackground::where('student_id', $student->id)->orderBy('year_ended', 'desc')->get();
        $parents          = $student->guardians()->whereIn('type', ['parent', 'guardian'])->get();
        $emergencyContact = $student->guardians()->where('is_emergency_contact', true)->first();
        $school           = $student->school;

        $pdf = Pdf::loadView('acad_enrolment.pdf.enrollment', [
            'enrollment'       => $enrollment,
            'student'          => $student,
            'term'             => $term,
            'pathway'          => $pathway,
            'program'          => $program,
            'modality'         => $modality,
            'node'             => $node,
            'backgrounds'      => $backgrounds,
            'skipped'          => $skipped,
            'parents'          => $parents,
            'emergencyContact' => $emergencyContact,
            'school'           => $school,
        ])->setPaper('a4');

        $filename = sprintf(
            'enrollments/%d/ENR-%s-%s.pdf',
            $student->id,
            str_pad($enrollment->id, 6, '0', STR_PAD_LEFT),
            now()->format('Ymd_His'),
        );
        Storage::disk('public')->put($filename, $pdf->output());
        $absPath = Storage::disk('public')->path($filename);

        // Collect unique recipients.
        $recipients = [];
        foreach ($parents as $p) {
            if (filter_var($p->email ?? null, FILTER_VALIDATE_EMAIL)) {
                $recipients[$p->email] = trim(($p->first_name ?? '').' '.($p->last_name ?? ''));
            }
        }
        if ($emergencyContact && filter_var($emergencyContact->email ?? null, FILTER_VALIDATE_EMAIL)) {
            $recipients[$emergencyContact->email] = trim(($emergencyContact->first_name ?? '').' '.($emergencyContact->last_name ?? ''));
        }

        foreach ($recipients as $email => $name) {
            try {
                Mail::to($email)->send(new EnrollmentSubmittedMail($enrollment, $absPath, $name));
            } catch (\Throwable $e) {
                Log::warning('Enrolment mail recipient failed', [
                    'enrollment_id' => $enrollment->id,
                    'email'         => $email,
                    'error'         => $e->getMessage(),
                ]);
            }
        }
    }

    /* =================================================================
     |  STEP 7 — CONFIRMATION
     | =================================================================*/

    public function confirmation($termId, $enrollmentId)
    {
        $term       = Term::findOrFail($termId);
        $enrollment = StudentEnrollment::with(['program', 'modality', 'educationNode'])
            ->findOrFail($enrollmentId);

        return view('acad_enrolment.shared.confirmation', compact('term', 'enrollment'));
    }

    /* =================================================================
     |  LEGACY — kept for backward compatibility but now redirect to the
     |  new pathway flow.
     | =================================================================*/

    public function showTrack($termId)
    {
        return redirect()->route('public.apply.pathway', $termId);
    }

    public function storeTrack(Request $request, $termId)
    {
        return redirect()->route('public.apply.pathway', $termId);
    }
}
