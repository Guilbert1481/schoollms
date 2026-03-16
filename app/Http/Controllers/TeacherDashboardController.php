<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject;

class TeacherDashboardController extends Controller
{
    public function index()
    {
        $subjects = Subject::where('teacher_id', Auth::id())->get();

        return view('teacher.dashboard', compact('subjects'));
    }
}
