<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Dashboard\StudentDashboardService;
use App\Services\Grading\StudentRecordsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Academics → Records: every graded activity (tests, homework) with the
 * running average from the principal's grading scheme (attendance excluded)
 * and the Subjects-at-Risk breakdown. Read-only for students — grades are
 * teacher-owned, so the row action is View, never Edit.
 */
class RecordsController extends Controller
{
    public function __construct(
        private StudentRecordsService $records,
        private StudentDashboardService $dashboard,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->where('school_id', $user->school_id)->first();
        abort_unless($student, 403);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'subject_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'integer', 'min:1', 'max:4'],
            'term_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $isBasicEd = $this->dashboard->isBasicEd($user);

        return view('student.records.index', [
            'rows' => $this->records->filter($this->records->rows($user), $filters),
            'filters' => $filters,
            'isBasicEd' => $isBasicEd,
            'subjects' => $this->records->subjectOptions($user),
            'terms' => $isBasicEd ? collect() : $this->records->termOptions($user),
            'average' => $this->records->runningAverage($user),
            'atRisk' => $this->records->atRisk($user),
        ]);
    }
}
