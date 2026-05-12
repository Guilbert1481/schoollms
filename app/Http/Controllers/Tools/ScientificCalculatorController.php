<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;

class ScientificCalculatorController extends Controller
{
    public function index()
    {
        return view('tools.scientific-calculator');
    }
}
