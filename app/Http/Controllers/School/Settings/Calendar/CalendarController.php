<?php

namespace App\Http\Controllers\School\Settings\Calendar;

use App\Http\Controllers\Controller;

class CalendarController extends Controller
{
    public function index()
    {
        return view('school.settings.master-data.calendar');
    }
}
