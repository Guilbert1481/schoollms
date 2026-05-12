<?php

namespace App\Http\Controllers\CourseArchitect;

use App\Http\Controllers\Controller;

class ProductionReportsController extends Controller
{
    public function index()
    {
        return view('course-architect.intelligence.production-reports');
    }
}
