<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;

class BudgetController extends Controller
{
    public function index()
    {
        return view('tools.budget');
    }
}
