<?php

namespace App\Http\Controllers\Teacher\Lesson;

use App\Http\Controllers\Controller;

class LessonController extends Controller
{
    public function index()
    {
        return view('teacher.lessons.index');
    }
}
