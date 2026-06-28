<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('principal.dashboard');
    }
}
