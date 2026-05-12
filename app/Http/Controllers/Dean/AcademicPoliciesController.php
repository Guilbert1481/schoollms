<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\AcademicPolicy;
use App\Models\Program;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dean Academic Policies CRUD.
 *
 * Lets a dean create, edit, archive, and activate per-(school, level, program, term)
 * academic policies that drive the AcademicPolicyResolver and validation pipeline.
 */
class AcademicPoliciesController extends Controller
{
    private const LEVELS = [
        'kinder', 'elementary', 'junior_high',
        'senior_high', 'undergraduate', 'graduate',
    ];

    public function index(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $query = AcademicPolicy::query()
            ->where('school_id', $schoolId)
            ->with(['program:id,code,name', 'term:id,name']);

        if ($request->filled('education_level')) {
            $query->where('education_level', $request->education_level);
        }
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $policies = $query
            ->orderByDesc('is_active')
            ->orderBy('education_level')
            ->orderBy('program_id')
            ->orderBy('term_id')
            ->paginate(15)
            ->withQueryString();

        $programs = Program::where('school_id', $schoolId)->orderBy('name')->get(['id', 'code', 'name']);
        $terms    = Term::orderByDesc('id')->get(['id', 'name']);

        return view('dean.academic_policies.index', [
            'policies' => $policies,
            'programs' => $programs,
            'terms'    => $terms,
            'levels'   => self::LEVELS,
            'filters'  => $request->only(['education_level', 'program_id', 'term_id', 'status']),
        ]);
    }

    public function create()
    {
        $schoolId = Auth::user()->school_id;
        $programs = Program::where('school_id', $schoolId)->orderBy('name')->get(['id', 'code', 'name']);
        $terms    = Term::orderByDesc('id')->get(['id', 'name']);

        return view('dean.academic_policies.create', [
            'policy'   => new AcademicPolicy(['is_active' => true]),
            'programs' => $programs,
            'terms'    => $terms,
            'levels'   => self::LEVELS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $data['school_id']  = Auth::user()->school_id;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        AcademicPolicy::create($data);

        return redirect()
            ->route('dean.academic_policies.index')
            ->with('success', 'Academic policy created.');
    }

    public function edit(AcademicPolicy $policy)
    {
        $this->authorizeSchool($policy);

        $schoolId = Auth::user()->school_id;
        $programs = Program::where('school_id', $schoolId)->orderBy('name')->get(['id', 'code', 'name']);
        $terms    = Term::orderByDesc('id')->get(['id', 'name']);

        return view('dean.academic_policies.edit', [
            'policy'   => $policy,
            'programs' => $programs,
            'terms'    => $terms,
            'levels'   => self::LEVELS,
        ]);
    }

    public function update(Request $request, AcademicPolicy $policy)
    {
        $this->authorizeSchool($policy);

        $data = $this->validatePayload($request);
        $data['updated_by'] = Auth::id();

        $policy->update($data);

        return redirect()
            ->route('dean.academic_policies.index')
            ->with('success', 'Academic policy updated.');
    }

    public function destroy(AcademicPolicy $policy)
    {
        $this->authorizeSchool($policy);
        $policy->delete();

        return redirect()
            ->route('dean.academic_policies.index')
            ->with('success', 'Academic policy deleted.');
    }

    public function toggle(AcademicPolicy $policy)
    {
        $this->authorizeSchool($policy);
        $policy->update([
            'is_active'  => ! $policy->is_active,
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', $policy->is_active ? 'Policy activated.' : 'Policy deactivated.');
    }

    /* ------------------------------------------------------------------ */
    /*  Internal helpers                                                  */
    /* ------------------------------------------------------------------ */

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'education_level'                => ['nullable', 'string', 'in:'.implode(',', self::LEVELS)],
            'program_id'                     => ['nullable', 'integer', 'exists:programs,id'],
            'term_id'                        => ['nullable', 'integer', 'exists:terms,id'],

            'min_units'                      => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'max_units'                      => ['nullable', 'numeric', 'min:0', 'max:99.99', 'gte:min_units'],
            'max_subjects'                   => ['nullable', 'integer', 'min:1', 'max:30'],
            'overload_threshold_units'       => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'max_section_capacity_override'  => ['nullable', 'integer', 'min:1', 'max:500'],

            'requires_payment_to_enrol'      => ['nullable', 'boolean'],
            'min_payment_percent'            => ['nullable', 'numeric', 'min:0', 'max:100'],

            'effective_from'                 => ['nullable', 'date'],
            'effective_to'                   => ['nullable', 'date', 'after_or_equal:effective_from'],

            'is_active'                      => ['nullable', 'boolean'],
        ]);
    }

    protected function authorizeSchool(AcademicPolicy $policy): void
    {
        abort_unless($policy->school_id === Auth::user()->school_id, 403);
    }
}
