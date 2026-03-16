<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Program::where('school_id', auth()->user()->school_id)
            ->latest()
            ->get();

        return view('academic.programs.index', compact('programs'));
    }

    public function create()
    {
        return view('academic.programs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'type' => 'required|string|max:100',
        ]);

        Program::create([
            'school_id' => auth()->user()->school_id,
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'is_active' => true,
        ]);

        return redirect()->route('admin.academic.programs.index')
            ->with('success', 'Program created successfully.');
    }
}
