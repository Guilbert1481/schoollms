<?php

namespace App\Http\Controllers\Training\Trainor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        return view('training.trainor.assessments', [
            'assessments' => [],
        ]);
    }

    public function show(Request $request, int $assessment)
    {
        return view('training.trainor.assessment_show', [
            'assessmentId' => $assessment,
        ]);
    }

    public function grade(Request $request, int $assessment)
    {
        return back()->with('success', 'Grades saved.');
    }
}
