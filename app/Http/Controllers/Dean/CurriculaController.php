<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Dean Curricula CRUD.
 *
 * Lets a dean create curricula per program, set the version, and pick the
 * "term mode" (2 = bi-semester, 3 = trimester). Both wizard tracks and the
 * BsedEnglishCurriculumSeeder honour curriculums.terms_per_year.
 */
class CurriculaController extends Controller
{
    private const TERM_MODES = [2, 3];

    public function index(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $query = Curriculum::query()
            ->where('school_id', $schoolId)
            ->with(['program:id,code,name'])
            ->withCount('subjects');

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $curricula = $query
            ->orderByDesc('is_active')
            ->orderBy('program_id')
            ->orderByDesc('version')
            ->paginate(15)
            ->withQueryString();

        $programs = Program::where('school_id', $schoolId)->orderBy('name')->get(['id', 'code', 'name']);

        return view('dean.curricula.index', [
            'curricula' => $curricula,
            'programs'  => $programs,
            'filters'   => $request->only(['program_id', 'status']),
        ]);
    }

    public function create()
    {
        $schoolId = Auth::user()->school_id;
        $programs = Program::where('school_id', $schoolId)->orderBy('name')->get(['id', 'code', 'name']);

        return view('dean.curricula.create', [
            'curriculum' => new Curriculum(['terms_per_year' => 2, 'is_active' => true]),
            'programs'   => $programs,
            'termModes'  => self::TERM_MODES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $data['school_id'] = Auth::user()->school_id;
        $data['has_summer_term'] = $request->boolean('has_summer_term');

        Curriculum::create($data);

        return redirect()
            ->route('dean.curricula.index')
            ->with('success', 'Curriculum created.');
    }

    public function show(Curriculum $curriculum)
    {
        $this->authorizeSchool($curriculum);

        $curriculum->load('program:id,code,name');

        // Pull the curriculum_subjects rows joined with subjects.
        $rows = DB::table('curriculum_subjects as cs')
            ->join('subjects as s', 's.id', '=', 'cs.subject_id')
            ->where('cs.curriculum_id', $curriculum->id)
            ->orderBy('cs.year_level')
            ->orderBy('cs.semester')
            ->orderBy('s.code')
            ->get([
                's.id', 's.code', 's.name',
                'cs.year_level', 'cs.semester', 'cs.units',
                'cs.is_core', 'cs.is_elective',
            ]);

        // Group year -> term -> [rows], compute per-term unit totals.
        $grouped = $rows
            ->groupBy('year_level')
            ->map(fn ($byYear) => $byYear->groupBy('semester'));

        $totalUnits = (float) $rows->sum('units');

        return view('dean.curricula.show', [
            'curriculum' => $curriculum,
            'grouped'    => $grouped,
            'rows'       => $rows,
            'totalUnits' => $totalUnits,
        ]);
    }

    public function edit(Curriculum $curriculum)
    {
        $this->authorizeSchool($curriculum);

        $schoolId = Auth::user()->school_id;
        $programs = Program::where('school_id', $schoolId)->orderBy('name')->get(['id', 'code', 'name']);

        return view('dean.curricula.edit', [
            'curriculum' => $curriculum,
            'programs'   => $programs,
            'termModes'  => self::TERM_MODES,
        ]);
    }

    public function update(Request $request, Curriculum $curriculum)
    {
        $this->authorizeSchool($curriculum);

        $data = $this->validatePayload($request, $curriculum->id);
        $data['has_summer_term'] = $request->boolean('has_summer_term');
        $curriculum->update($data);

        return redirect()
            ->route('dean.curricula.index')
            ->with('success', 'Curriculum updated.');
    }

    public function destroy(Curriculum $curriculum)
    {
        $this->authorizeSchool($curriculum);
        $curriculum->delete();

        return redirect()
            ->route('dean.curricula.index')
            ->with('success', 'Curriculum deleted.');
    }

    public function toggle(Curriculum $curriculum)
    {
        $this->authorizeSchool($curriculum);
        $curriculum->update(['is_active' => ! $curriculum->is_active]);

        return back()->with('success', $curriculum->is_active ? 'Curriculum activated.' : 'Curriculum deactivated.');
    }

    /* ------------------------------------------------------------------ */

    protected function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'program_id'     => ['required', 'integer', 'exists:programs,id'],
            'name'           => ['required', 'string', 'max:255'],
            'version'        => ['required', 'string', 'max:32'],
            'terms_per_year' => ['required', 'integer', 'in:'.implode(',', self::TERM_MODES)],
            'has_summer_term'=> ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'is_active'      => ['nullable', 'boolean'],
        ]);
    }

    protected function authorizeSchool(Curriculum $curriculum): void
    {
        abort_unless($curriculum->school_id === Auth::user()->school_id, 403);
    }
}
