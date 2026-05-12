<?php

namespace App\Http\Controllers\CourseArchitect;

use App\Http\Controllers\Controller;

class PreviewController extends Controller
{
    public function index()
    {
        return view('course-architect.workspace.preview');
    }
}
