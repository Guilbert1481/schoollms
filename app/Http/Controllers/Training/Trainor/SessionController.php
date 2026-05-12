<?php

namespace App\Http\Controllers\Training\Trainor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        return view('training.trainor.sessions', [
            'sessions' => [],
        ]);
    }

    public function show(Request $request, int $session)
    {
        return view('training.trainor.session_show', [
            'sessionId' => $session,
        ]);
    }
}
