<?php

namespace App\Http\Controllers\GuidanceCounselor;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('guidance_counselor.dashboard');
    }
}
