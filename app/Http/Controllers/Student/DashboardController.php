<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Pass the approved modality to drive the sidebar logic
        $activeModality = $user->active_modality ?? $user->enrollment_type;

        return view('student.dashboard', compact('user', 'activeModality'));
    }
}