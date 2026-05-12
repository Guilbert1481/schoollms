<?php

namespace App\Http\Controllers\Training\Trainor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        return view('training.trainor.certificates', [
            'certificates' => [],
        ]);
    }

    public function issue(Request $request, int $enrollment)
    {
        return back()->with('success', 'Certificate issued.');
    }
}
