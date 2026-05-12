<?php

namespace App\Http\Controllers\Admin\School\Settings\Assignments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Program;
use App\Models\EducationNode;

class ProgramsController extends Controller
{
    public function indexPrograms()
    {
        $schoolId = auth()->user()->school_id;

        $programHeads = DB::table('users')
            ->join('profiles', 'profiles.user_id', '=', 'users.id')
            ->where('users.school_id', $schoolId)
            ->where('users.role', 'program_head')
            ->select(
                'users.id',
                DB::raw("CONCAT(profiles.first_name,' ',profiles.last_name) as name")
            )
            ->orderBy('profiles.last_name')
            ->get();

        $colleges = DB::table('colleges')
            ->where('school_id', $schoolId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $programs = DB::table('programs as pr')
            ->leftJoin('colleges as c', 'c.id', '=', 'pr.college_id')
            ->leftJoin('users as u', 'u.id', '=', 'pr.program_head_id')
            ->leftJoin('profiles as p', 'p.user_id', '=', 'u.id')
            ->where('pr.school_id', $schoolId)
            ->select(
                'pr.id',
                'pr.code',
                'pr.name',
                'pr.college_id',
                'pr.education_node_id',
                'pr.program_head_id',
                DB::raw("COALESCE(c.name, 'Unassigned') as college_name"),
                DB::raw("COALESCE(CONCAT(p.first_name,' ',p.last_name),'Unassigned') as program_head_name")
            )
            ->orderBy('pr.name')
            ->get();

        $educationNodes = EducationNode::where('is_active', true)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'node_type'])
            ->map(fn ($n) => [
                'id'        => $n->id,
                'parent_id' => $n->parent_id,
                'name'      => $n->name,
                'node_type' => $n->node_type,
            ])
            ->values();

        return view('admin.assignments.index', [
            'programs'       => $programs,
            'colleges'       => $colleges,
            'programHeads'   => $programHeads,
            'educationNodes' => $educationNodes,
        ]);
    }

    public function createProgram(Request $request)
    {
        $request->validate([
            'code'              => 'required|string|max:50',
            'name'              => 'required|string|max:255',
            'college_id'        => 'required|exists:colleges,id',
            'education_node_id' => 'nullable|integer|exists:education_nodes,id',
        ]);

        Program::create([
            'school_id'         => auth()->user()->school_id,
            'code'              => $request->code,
            'name'              => $request->name,
            'college_id'        => $request->college_id,
            'education_node_id' => $request->education_node_id ?: null,
            'active'            => true,
        ]);

        return back()->with('success', 'Program created successfully.');
    }

    public function updateProgramInfo(Request $request, $id)
    {
        $request->validate([
            'code'              => 'required|string|max:50',
            'name'              => 'required|string|max:255',
            'college_id'        => 'required|exists:colleges,id',
            'education_node_id' => 'nullable|integer|exists:education_nodes,id',
        ]);

        $program = Program::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $program->update($request->only('code', 'name', 'college_id', 'education_node_id'));

        return back()->with('success', 'Program updated successfully.');
    }

    public function destroyProgram($id)
    {
        $program = Program::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $program->delete();

        return back()->with('success', 'Program deleted successfully.');
    }

    public function storeProgramhead(Request $request)
    {
        $program = Program::where('school_id', auth()->user()->school_id)
            ->findOrFail($request->program_id);

        $program->update(['program_head_id' => $request->program_head_id ?: null]);

        return back()->with('success', 'Program Head assigned successfully.');
    }

    public function updateProgramhead(Request $request, $id)
    {
        $program = Program::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $program->update(['program_head_id' => $request->program_head_id ?: null]);

        return back()->with('success', 'Program Head updated successfully.');
    }
}