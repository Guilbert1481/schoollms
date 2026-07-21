<?php

namespace App\Http\Controllers\Student\Services;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
use App\Models\Student;
use App\Services\Clearance\ClearanceBuilder;
use App\Services\Dashboard\StudentDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Student side of clearance: a Request tab (start a clearance for the active
 * enrollment) and a Status tab (per-signatory sign-off rows). One open
 * clearance per enrollment at a time.
 */
class ClearanceController extends Controller
{
    public function __construct(
        private StudentDashboardService $dashboard,
        private ClearanceBuilder $builder,
    ) {}

    public function index(Request $request)
    {
        $student = $this->student();
        $enrollment = $this->dashboard->activeEnrollment(Auth::user());

        $clearances = Clearance::query()
            ->where('student_id', $student->id)
            ->with(['items' => fn ($q) => $q->orderBy('id')])
            ->orderByDesc('id')
            ->get();

        $open = $clearances->first(fn ($c) => $c->isOpen());

        return view('student.services.clearance', [
            'enrollment' => $enrollment,
            'clearances' => $clearances,
            'open' => $open,
            'purposes' => Clearance::PURPOSES,
            // Land on Status whenever there is anything to show there.
            'tab' => $request->query('tab', $clearances->isNotEmpty() ? 'status' : 'request'),
        ]);
    }

    public function store(Request $request)
    {
        $student = $this->student();
        $enrollment = $this->dashboard->activeEnrollment(Auth::user());
        abort_unless($enrollment, 404);

        $validated = $request->validate([
            'purpose' => ['required', Rule::in(Clearance::PURPOSES)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $alreadyOpen = Clearance::query()
            ->where('student_enrollment_id', $enrollment->id)
            ->where('status', '!=', Clearance::STATUS_COMPLETED)
            ->exists();
        if ($alreadyOpen) {
            return back()->withErrors(['purpose' => 'You already have a clearance in progress for this enrollment.']);
        }

        $this->builder->start($enrollment, $validated['purpose'], $validated['note'] ?? null);

        return redirect()
            ->route('student.services.clearance.index', ['tab' => 'status'])
            ->with('success', 'Clearance started. Follow each office\'s sign-off on the Status tab.');
    }

    private function student(): Student
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->where('school_id', $user->school_id)->first();
        abort_unless($student, 403);

        return $student;
    }
}
