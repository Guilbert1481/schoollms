<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Program;
use App\Models\EducationNode;

class ProgramsController extends Controller
{
    public function index(Request $request)
    {
        $query = Program::orderBy('name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $programs = $query->paginate(10)->withQueryString();

        // Education nodes the program can be filed under (e.g., Bachelor's,
        // Master's Degree, etc.). Grouped by their root level for the dropdown.
        $programTypeOptions = EducationNode::with('parent.parent.parent.parent')
            ->whereIn('node_type', ['program_type', 'level', 'stage', 'track', 'strand'])
            ->where('is_active', true)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get()
            ->map(fn ($n) => ['id' => $n->id, 'label' => $n->full_path])
            ->values();

        return view('dean.programs.index', compact('programs', 'programTypeOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:32',
            'description' => 'nullable|string|max:1000',
            'active' => 'required|boolean',
            'education_node_id' => 'nullable|integer|exists:education_nodes,id',
        ]);
        $validated['active'] = (int) $request->active; // ensure it's 0 or 1
        $validated['school_id'] = auth()->user()->school_id;

        Program::create($validated);
        return redirect()->route('dean.programs.index')->with('success', 'Program added!');
    }

    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:32',
            'description' => 'nullable|string|max:1000',
            'active' => 'required|boolean',
            'education_node_id' => 'nullable|integer|exists:education_nodes,id',
        ]);
        $validated['active'] = (int) $request->active; // ensure it's 0 or 1

        $program->update($validated);
        return redirect()->route('dean.programs.index')->with('success', 'Program updated!');
    }

    public function destroy($id)
    {
        $program = Program::findOrFail($id);
        $program->delete();
        return redirect()->route('dean.programs.index')->with('success', 'Program deleted!');
    }
}