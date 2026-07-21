<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Teacher student roster. Mirrors the homework/attendance class-picker pattern:
 * the teacher picks one of their classes and sees the enrolled students. Scoped
 * to teacher_id so a teacher only ever sees rosters for classes they teach.
 */
class StudentRosterController extends Controller
{
    public function index(Request $request)
    {
        $classes = ClassModel::where('teacher_id', Auth::id())
            ->with(['subject:id,name', 'section:id,name'])
            ->orderBy('code')
            ->get();

        $context = null;
        if ($request->filled('class_id')) {
            $class = $classes->firstWhere('id', (int) $request->query('class_id'));
            if ($class) {
                // Same roster source as attendance's session roster — students
                // enrolled in this class, name-sorted for a stable list.
                $students = $class->students()
                    ->get(['students.id', 'first_name', 'middle_name', 'last_name', 'student_number'])
                    ->sortBy(fn ($s) => mb_strtolower($s->last_name.' '.$s->first_name))
                    ->values();

                $context = [
                    'class' => $class,
                    'students' => $students,
                ];
            }
        }

        return view('teacher.students.index', compact('classes', 'context'));
    }
}
