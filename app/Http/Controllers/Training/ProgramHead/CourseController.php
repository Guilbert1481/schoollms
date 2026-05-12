<?php

namespace App\Http\Controllers\Training\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\TrainingCourse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = TrainingCourse::query()
            ->orderBy('course_name')
            ->paginate(15);

        return view('training.program_head.courses.index', [
            'courses' => $courses,
        ]);
    }

    public function show(Request $request, int $course)
    {
        $row = TrainingCourse::findOrFail($course);

        return view('training.program_head.courses.show', [
            'course' => $row,
        ]);
    }
}
