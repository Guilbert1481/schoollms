<?php

namespace App\Http\Controllers\CourseArchitect;

use App\Http\Controllers\Controller;

class LearningAnalyticsController extends Controller
{
    public function index()
    {
        return view('course-architect.intelligence.learning-analytics');
    }
}
