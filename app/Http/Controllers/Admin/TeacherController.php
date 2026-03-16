<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    /**
     * Show list of teachers
     */
    public function index()
    {
        $teachers = User::where('role', 'teacher')->get();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function dashboard()
    {
        return view('teacher.dashboard');
    }

    /**
     * Show add teacher form
     */
    public function create()
    {
        return view('admin.teachers.create');
    }

    /**
     * Store teacher
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        if (!auth()->user()->school_id) {
            abort(403, 'Admin has no school assigned.');
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'teacher',
            'school_id' => auth()->user()->school_id,
        ]);


        return redirect('/admin/teachers')
            ->with('success', 'Teacher added successfully');
    }
}
