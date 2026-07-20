<?php

namespace App\Http\Controllers\Staff\Admissions;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Services\Scheduler\SectionAutoGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionsController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? null;

        $termId = $request->integer('term_id') ?: optional(
            DB::table('terms')->where('school_id', $schoolId)
                ->orderByDesc('is_current')
                ->orderByDesc('start_date')
                ->first()
        )->id;

        $terms = DB::table('terms')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        $sections = Section::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->with(['program:id,name,code', 'educationNode:id,name'])
            ->orderBy('education_node_id')
            ->orderBy('program_id')
            ->orderBy('year_level')
            ->orderBy('name')
            ->get();

        $programs = DB::table('programs')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // Basic-ed grade levels that actually have a curriculum for this school.
        $gradeNodes = DB::table('grade_level_subjects as gls')
            ->join('subjects as s', 's.id', '=', 'gls.subject_id')
            ->join('education_nodes as n', 'n.id', '=', 'gls.education_node_id')
            ->where('gls.is_active', 1)
            ->when($schoolId, fn ($q) => $q->where('s.school_id', $schoolId))
            ->where('n.is_active', 1)
            ->select('n.id', 'n.name')
            ->groupBy('n.id', 'n.name')
            ->orderBy('n.id')
            ->get();

        return view('admission.sections.index', compact('sections', 'terms', 'termId', 'programs', 'gradeNodes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'term_id' => 'required|integer|exists:terms,id',
            // A section belongs to a program cohort (higher ed) OR a grade
            // level (basic ed) — exactly one of the two.
            'program_id' => 'nullable|required_without:education_node_id|prohibits:education_node_id|integer|exists:programs,id',
            'education_node_id' => 'nullable|integer|exists:education_nodes,id',
            'year_level' => 'nullable|required_with:program_id|integer|min:1|max:8',
            'name' => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:1|max:500',
        ]);

        $schoolId = auth()->user()->school_id ?? null;
        $term = DB::table('terms')->where('id', $data['term_id'])->first();

        Section::create([
            'school_id' => $schoolId,
            'program_id' => $data['program_id'] ?? null,
            'education_node_id' => $data['education_node_id'] ?? null,
            'term_id' => $data['term_id'],
            'name' => $data['name'],
            'year_level' => $data['year_level'] ?? null,
            'capacity' => $data['capacity'] ?? 40,
            'is_active' => 0,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('admission.sections.index', ['term_id' => $data['term_id']])
            ->with('success', "Section \"{$data['name']}\" created as draft.");
    }

    public function autoGenerate(Request $request, SectionAutoGenerator $generator)
    {
        $data = $request->validate([
            'term_id' => 'required|integer|exists:terms,id',
            'sections_per_cohort' => 'nullable|integer|min:1|max:5',
            'capacity' => 'nullable|integer|min:1|max:500',
        ]);

        $schoolId = auth()->user()->school_id ?? null;

        $result = $generator->generateForTerm(
            (int) $data['term_id'],
            $schoolId,
            (int) ($data['sections_per_cohort'] ?? 1),
            (int) ($data['capacity'] ?? 40)
        );

        return redirect()
            ->route('admission.sections.index', ['term_id' => $data['term_id']])
            ->with('success', sprintf(
                'Auto-generated %d new section(s); %d already existed.',
                $result['created'],
                $result['skipped']
            ));
    }

    public function destroy(Section $section)
    {
        if ($section->status === 'published') {
            return back()->with('error', 'Cannot delete a published section. Unpublish it first.');
        }

        $section->delete();

        return back()->with('success', 'Section deleted.');
    }

    public function publish(Section $section)
    {
        $section->update(['status' => 'published', 'is_active' => 1]);

        return back()->with('success', "Section \"{$section->name}\" published.");
    }

    public function unpublish(Section $section)
    {
        $section->update(['status' => 'draft']);

        return back()->with('success', "Section \"{$section->name}\" reverted to draft.");
    }

    public function publishAll(Request $request)
    {
        $request->validate(['term_id' => 'required|integer']);
        $schoolId = auth()->user()->school_id ?? null;

        $count = Section::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('term_id', $request->integer('term_id'))
            // Same predicate the page renders as "Draft": anything neither
            // published nor archived, so stray legacy statuses get swept too.
            ->whereNotIn('status', ['published', 'archived'])
            ->update(['status' => 'published', 'is_active' => 1, 'updated_at' => now()]);

        return back()->with('success', "Published {$count} draft section(s).");
    }
}
