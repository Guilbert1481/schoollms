<?php

namespace App\Http\Controllers\Tools\VideoConference;

use App\Http\Controllers\Controller;

class AttendanceController extends Controller
{
    public function index()
    {
        return view('tools.video-conference.attendance');
    }
}
