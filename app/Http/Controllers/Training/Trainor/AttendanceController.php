<?php

namespace App\Http\Controllers\Training\Trainor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        return view('training.trainor.attendance', [
            'sessions' => [],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer',
            'attendance' => 'array',
        ]);

        return back()->with('success', 'Attendance saved.');
    }
}
