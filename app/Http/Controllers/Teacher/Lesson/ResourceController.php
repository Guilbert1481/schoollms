<?php

namespace App\Http\Controllers\Teacher\Lesson;

use App\Http\Controllers\Controller;

class ResourceController extends Controller
{
    public function index()
    {
        return view('teacher.lessons.resources');
    }
}
