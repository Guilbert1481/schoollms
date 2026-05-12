<?php

namespace App\Http\Controllers\CourseArchitect;

use App\Http\Controllers\Controller;

class MediaOptimizerController extends Controller
{
    public function index()
    {
        return view('course-architect.assets.media-optimizer');
    }
}
