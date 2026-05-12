<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;

class EditPDFController extends Controller
{
    public function index()
    {
        return view('tools.edit-pdf');
    }
}
