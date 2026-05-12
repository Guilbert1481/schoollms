<?php

namespace App\Http\Controllers\CourseArchitect;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'attached_competency_pct' => 80,
            'pending_topics'          => 12,
            'published_lessons'       => 47,
            'active_paths'            => 6,
        ];
        return view('course-architect.workspace.dashboard', compact('stats'));
    }
}
