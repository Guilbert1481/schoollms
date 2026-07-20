<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Services\Academics\SectionClassBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registrar → Section Classes: build a basic-ed section's classes in one go —
 * every learning area of the grade with its teacher, plus the section adviser.
 * Composes with Teaching Assignments (same class upsert key).
 */
class SectionClassesController extends Controller
{
    public function show(Request $request, Section $section, SectionClassBuilder $builder)
    {
        $this->guard($section);

        $subjects = $builder->subjectsFor($section);

        $teachers = DB::table('users')
            ->where('school_id', $section->school_id)->where('role', 'teacher')
            ->orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($u) => ['id' => (int) $u->id, 'name' => trim("{$u->last_name}, {$u->first_name}")])
            ->values();

        $section->load('educationNode:id,name');

        return view('registrar.section-classes.show', compact('section', 'subjects', 'teachers'));
    }

    public function store(Request $request, Section $section, SectionClassBuilder $builder)
    {
        $this->guard($section);

        $data = $request->validate([
            'teachers' => 'nullable|array',
            'teachers.*' => 'nullable|integer',
            'adviser_id' => 'nullable|integer',
        ]);

        if (array_key_exists('adviser_id', $data)) {
            $adviserId = $data['adviser_id'] ? (int) $data['adviser_id'] : null;
            if ($adviserId) {
                abort_unless(DB::table('users')
                    ->where('id', $adviserId)
                    ->where('school_id', $section->school_id)
                    ->where('role', 'teacher')
                    ->exists(), 422);
            }
            $section->update(['adviser_id' => $adviserId]);
        }

        $result = $builder->build($section, $data['teachers'] ?? []);

        return redirect()
            ->route('registrar.section-classes.show', $section)
            ->with('success', sprintf(
                'Saved: %d class(es) created, %d teacher(s) changed.',
                $result['created'],
                $result['updated']
            ));
    }

    /** Basic-ed sections of the actor's school only. */
    private function guard(Section $section): void
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless($schoolId && (int) $section->school_id === $schoolId, 404);
        abort_unless($section->education_node_id, 404);
    }
}
