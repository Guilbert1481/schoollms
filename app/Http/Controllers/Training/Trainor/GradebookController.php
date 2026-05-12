<?php

namespace App\Http\Controllers\Training\Trainor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GradebookController extends Controller
{
    public function index(Request $request)
    {
        return view('training.trainor.gradebook', [
            'rows' => [],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'grades' => 'array',
        ]);

        return back()->with('success', 'Gradebook updated.');
    }
}
