<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subject;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'teachersCount' => User::where('role', 'teacher')->count(),
            'studentsCount' => User::where('role', 'student')->count(),
            'subjectsCount' => Subject::count(),
        ]);
    }
}
