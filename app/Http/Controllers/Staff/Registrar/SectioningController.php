<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Services\Academics\SectioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registrar → Sectioning Workbench (basic ed). Lists enrolled-but-unsectioned
 * students per grade with their grade's published sections, supports bulk
 * assignment, and renders auto-distribution proposals the registrar confirms.
 */
class SectioningController extends Controller
{
    public function index(Request $request, SectioningService $sectioning)
    {
        [$schoolId, $termId, $terms] = $this->context($request);

        $grades = $termId ? $sectioning->overview($schoolId, $termId) : collect();

        return view('registrar.sectioning.index', [
            'grades' => $grades,
            'terms' => $terms,
            'termId' => $termId,
            'proposal' => null,
            'proposalNode' => null,
        ]);
    }

    /** Manual bulk assignment: selected students → one section. */
    public function assign(Request $request, SectioningService $sectioning)
    {
        [$schoolId, $termId] = $this->context($request);

        $data = $request->validate([
            'term_id' => 'required|integer',
            'section_id' => 'required_without:placements|nullable|integer',
            'enrollment_ids' => 'required_without:placements|nullable|array',
            'enrollment_ids.*' => 'integer',
            // Applying a distribution proposal: enrollment_id => section_id.
            'placements' => 'nullable|array',
            'placements.*' => 'integer',
        ]);

        $placements = $data['placements']
            ?? array_fill_keys($data['enrollment_ids'] ?? [], (int) $data['section_id']);

        $count = $sectioning->apply($schoolId, $termId, $placements);

        return redirect()
            ->route('registrar.sectioning.index', ['term_id' => $termId])
            ->with('success', "Sectioned {$count} student(s).");
    }

    /** Build an auto-distribution proposal for one grade (nothing is saved). */
    public function distribute(Request $request, SectioningService $sectioning)
    {
        [$schoolId, $termId, $terms] = $this->context($request);

        $data = $request->validate([
            'term_id' => 'required|integer',
            'education_node_id' => 'required|integer',
            'strategy' => 'required|in:alphabetical,random',
        ]);

        $proposal = $sectioning->plan($schoolId, $termId, (int) $data['education_node_id'], $data['strategy']);

        return view('registrar.sectioning.index', [
            'grades' => $sectioning->overview($schoolId, $termId),
            'terms' => $terms,
            'termId' => $termId,
            'proposal' => $proposal,
            'proposalNode' => (int) $data['education_node_id'],
        ]);
    }

    /** @return array{0:int, 1:int, 2:\Illuminate\Support\Collection} */
    private function context(Request $request): array
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless($schoolId, 404);

        $terms = DB::table('terms')
            ->where('school_id', $schoolId)
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        $termId = $request->integer('term_id') ?: optional(
            DB::table('terms')->where('school_id', $schoolId)
                ->orderByDesc('is_current')
                ->orderByDesc('start_date')
                ->first()
        )->id;

        return [$schoolId, (int) $termId, $terms];
    }
}
