<?php

namespace App\Http\Controllers\CourseArchitect;

use App\Http\Controllers\Controller;

class ResourceVaultController extends Controller
{
    public function index()
    {
        return view('course-architect.assets.resource-vault');
    }
}
