<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\College;
use App\Models\Program;
use App\Models\Office;

class AssignmentManagementController extends Controller
{
    // ================= COLLEGES PAGE =================
    public function indexColleges()
    {
        $deans = User::where('role', 'dean')->get();
        $colleges = College::with('dean')->get();

        return view('admin.assignments.index', compact(
            'deans',
            'colleges'
        ));
    }

    // ================= PROGRAMS PAGE =================
    public function indexPrograms()
    {
        $programHeads = User::where('role', 'program_head')->get();
        $programs = Program::with(['college', 'programHead'])->get();

        return view('admin.assignments.index', compact(
            'programHeads',
            'programs'
        ));
    }

    // ================= OFFICES PAGE =================
    public function indexOffices()
    {
        $officeHeads = User::where('role', 'office_head')->get();
        $offices = Office::with('officeHead')->get();

        return view('admin.assignments.index', compact(
            'officeHeads',
            'offices'
        ));
    }

    // ================= COLLEGES =================
    public function storeColleges(Request $request)
    {
        $request->validate([
            'college_id' => 'required|exists:colleges,id',
            'dean_id' => 'nullable|exists:users,id'
        ]);

        $college = College::findOrFail($request->college_id);
        $college->update(['dean_id' => $request->dean_id]);

        return back()->with('success', 'Dean assigned successfully.');
    }

    public function updateColleges(Request $request, $id)
    {
        $college = College::findOrFail($id);
        $college->update(['dean_id' => $request->dean_id]);

        return back()->with('success', 'Dean updated successfully.');
    }

    // ================= PROGRAM HEAD =================
    public function storeProgramhead(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'program_head_id' => 'nullable|exists:users,id'
        ]);

        $program = Program::findOrFail($request->program_id);
        $program->update(['program_head_id' => $request->program_head_id]);

        return back()->with('success', 'Program head assigned successfully.');
    }

    public function updateProgramhead(Request $request, $id)
    {
        $program = Program::findOrFail($id);
        $program->update(['program_head_id' => $request->program_head_id]);

        return back()->with('success', 'Program head updated successfully.');
    }

    // ================= OFFICES =================
    public function storeOffices(Request $request)
    {
        $request->validate([
            'office_id' => 'required|exists:offices,id',
            'office_head_id' => 'nullable|exists:users,id'
        ]);

        $office = Office::findOrFail($request->office_id);
        $office->update(['office_head_id' => $request->office_head_id]);

        return back()->with('success', 'Office head assigned successfully.');
    }

    public function updateOffices(Request $request, $id)
    {
        $office = Office::findOrFail($id);
        $office->update(['office_head_id' => $request->office_head_id]);

        return back()->with('success', 'Office head updated successfully.');
    }
}