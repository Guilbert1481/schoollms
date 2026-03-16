<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Test;
use App\Traits\BuildsTestSections;

class PreviewTestController extends Controller
{
    use BuildsTestSections;
    
    public function preview(Request $request, $testId)
    {
        // Load test WITH questions and choices (ordered properly)
        $test = Test::with([
            'questions' => function ($q) {
                $q->orderBy('order');
            },
            'questions.choices'
        ])->findOrFail($testId);

        return view('teacher.tests.previewTest', [
            'test' => $test,

            // Header data (safe defaults so preview never breaks)
            'header' => [
                'school'  => $request->input('school', 'SCHOOL LETTERHEAD WITH LOGO'),
                'dept'    => $request->input('dept', ''),
                'subject' => $request->input('subject', $test->subject->name ?? ''),
                'teacher' => $request->input('teacher', auth()->user()->name ?? ''),
                'year'    => $request->input('year', ''),
            ]
        ]);
    }
}
