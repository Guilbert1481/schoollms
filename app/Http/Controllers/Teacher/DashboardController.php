<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('name')->get();

        return view('teacher.dashboard', compact('subjects'));
    }
}
