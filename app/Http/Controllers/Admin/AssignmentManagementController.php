<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\College;
use App\Models\Program;

class AssignmentManagementController extends Controller
{
    public function index()
    {
        $deans = User::where('role', 'dean')->get();
        $programHeads = User::where('role', 'program_head')->get();

        $colleges = College::with('dean')->get();
        $programs = Program::with(['college', 'programHead'])->get();

        return view('admin.assignments.index', compact(
            'deans',
            'programHeads',
            'colleges',
            'programs'
        ));
    }

    public function assignDean(Request $request)
    {
        $request->validate([
            'college_id' => 'required|exists:colleges,id',
            'dean_id' => 'nullable|exists:users,id'
        ]);

        $college = College::findOrFail($request->college_id);

        if ($request->dean_id) {
            $dean = User::findOrFail($request->dean_id);

            if ($dean->role !== 'dean') {
                abort(403, 'Invalid dean assignment.');
            }
        }

        $college->update([
            'dean_id' => $request->dean_id
        ]);

        return back()->with('success', 'Dean assignment updated successfully.');
    }

    public function assignProgramHead(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'program_head_id' => 'nullable|exists:users,id'
        ]);

        $program = Program::findOrFail($request->program_id);

        if ($request->program_head_id) {
            $head = User::findOrFail($request->program_head_id);

            if ($head->role !== 'program_head') {
                abort(403, 'Invalid program head assignment.');
            }
        }

        $program->update([
            'program_head_id' => $request->program_head_id
        ]);

        return back()->with('success', 'Program head assignment updated successfully.');
    }
}