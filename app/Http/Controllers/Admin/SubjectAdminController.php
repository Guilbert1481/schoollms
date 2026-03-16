<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;

class SubjectAdminController extends Controller
{
    public function show(Subject $subject)
    {
        return view('admin.subjects.subject-admin', compact('subject'));
    }
}
