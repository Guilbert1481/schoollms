<?php

namespace App\Http\Controllers\Tools\VideoConference;

use App\Http\Controllers\Controller;

class HistoryController extends Controller
{
    public function index()
    {
        return view('tools.video-conference.history');
    }
}
