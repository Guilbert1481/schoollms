<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Semester;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

class StudentRegisterController extends Controller
{
    public function show($semesterId)
    {
        $semester = Semester::findOrFail($semesterId);

        return view('auth.student_register', compact('semester'));
    }

    public function store(Request $request, $semesterId)
    {
        $semester = Semester::findOrFail($semesterId);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['first_name'] . ' ' . $validated['last_name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'student',
        ]);

        Student::create([
            'user_id'    => $user->id,
            'school_id'  => $semester->school_id,
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
        ]);

        auth()->login($user);

        return redirect('/apply/' . $semester->id);
    }
}
