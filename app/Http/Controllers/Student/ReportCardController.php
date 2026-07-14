<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Academics\ReportCardService;
use Illuminate\Support\Facades\Auth;

/**
 * The student "Grades" sidebar item — their Report Card for the current period
 * (per grading period for basic ed, per semester for higher ed).
 */
class ReportCardController extends Controller
{
    public function index(ReportCardService $service)
    {
        $user    = Auth::user();
        $student = $user?->student ?? Student::where('user_id', $user?->id)->first();

        return view('student.report-card.index', [
            'student'    => $student,
            'report'     => $student ? $service->build($student) : null,
            'rcEditable' => false,
        ]);
    }
}
