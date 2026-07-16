<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Support\SubjectScope;

class DashboardController extends Controller
{
    public function index()
    {
        $subjects = SubjectScope::forTeacher(Subject::query())->orderBy('name')->get();

        return view('teacher.dashboard', compact('subjects'));
    }
}
