<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class TrainingDashboardController extends Controller
{
    public function index()
    {
        $studentId = auth()->id();

        // ✅ Get enrolled courses
        $courses = DB::table('training_enrollments as te')
            ->join('training_courses as tc', 'tc.id', '=', 'te.course_id')
            ->where('te.trainee_id', $studentId)
            ->select('tc.id', 'tc.title', 'tc.description')
            ->get();

        // ✅ Get basic progress (placeholder for now)
        $progress = DB::table('training_assessment_scores')
            ->where('trainee_id', $studentId)
            ->selectRaw('COUNT(*) as attempts, SUM(score) as total_score')
            ->first();

        return view('student.training.dashboard', compact('courses', 'progress'));
    }
}