<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Auth;

/**
 * Teacher "My Classes" hub. Lists every class the signed-in teacher is assigned
 * (tenant-scoped by teacher_id), each linking to the class-scoped tools —
 * attendance, gradebook, homework, roster — so a teacher can start from one
 * place instead of re-picking the class on every page.
 */
class ClassListController extends Controller
{
    public function index()
    {
        $classes = ClassModel::where('teacher_id', Auth::id())
            ->with(['subject:id,name', 'section:id,name'])
            ->withCount('students')
            ->orderBy('code')
            ->get();

        return view('teacher.classes.index', compact('classes'));
    }
}
