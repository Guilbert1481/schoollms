<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    public function dashboard()
    {
        return view('academics.dashboard');
    }

    
}
