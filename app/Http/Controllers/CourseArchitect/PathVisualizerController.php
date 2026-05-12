<?php

namespace App\Http\Controllers\CourseArchitect;

use App\Http\Controllers\Controller;

class PathVisualizerController extends Controller
{
    public function index()
    {
        return view('course-architect.workspace.path-visualizer');
    }
}
