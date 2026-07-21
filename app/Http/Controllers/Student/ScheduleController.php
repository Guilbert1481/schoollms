<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\StudentDashboardService;
use Illuminate\Support\Facades\Auth;

/**
 * The student's weekly class timetable — the recurring class_schedules pattern
 * for every class they're enrolled in this term, grouped by weekday.
 */
class ScheduleController extends Controller
{
    public function index(StudentDashboardService $dashboard)
    {
        $user = Auth::user();

        return view('student.schedule.index', [
            'schedule' => $dashboard->weeklySchedule($user),
        ]);
    }
}
