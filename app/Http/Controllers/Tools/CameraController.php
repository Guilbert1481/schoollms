<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;

class CameraController extends Controller
{
    public function index()
    {
        return view('tools.camera');
    }
}
