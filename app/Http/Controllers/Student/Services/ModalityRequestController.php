<?php

namespace App\Http\Controllers\Student\Services;

use App\Http\Controllers\Controller;
use App\Models\Modality;
use App\Models\ModalityRequest;
use App\Models\Student;
use App\Services\Dashboard\StudentDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Student side of modality change requests. Non-basic-ed only (basic ed 404s —
 * the item is also hidden from their sidebar), and submissions are accepted
 * only within 2 weeks of the official enrollment date. The registrar decides.
 */
class ModalityRequestController extends Controller
{
    public function __construct(private StudentDashboardService $dashboard) {}

    public function index()
    {
        [$student, $enrollment] = $this->guard();

        $requests = ModalityRequest::query()
            ->where('student_id', $student->id)
            ->with(['fromModality', 'toModality'])
            ->orderByDesc('id')
            ->get();

        return view('student.services.modality', [
            'enrollment' => $enrollment,
            'current' => $enrollment?->modality,
            'options' => $enrollment ? $this->options($enrollment->school_id) : collect(),
            'windowOpen' => $this->dashboard->modalityWindowOpen(Auth::user()),
            'deadline' => $enrollment?->modalityRequestDeadline(),
            'requests' => $requests,
            'hasPending' => $requests->contains(fn ($r) => $r->isPending()),
        ]);
    }

    public function store(Request $request)
    {
        [$student, $enrollment] = $this->guard();

        abort_unless($enrollment, 404);
        abort_unless($this->dashboard->modalityWindowOpen(Auth::user()), 403,
            'The modality request window has closed for this enrollment.');

        $validated = $request->validate([
            'to_modality_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $option = $this->options($enrollment->school_id)->firstWhere('id', (int) $validated['to_modality_id']);
        if (! $option) {
            return back()->withErrors(['to_modality_id' => 'That modality is not offered by your school.']);
        }
        if ((int) $option->id === (int) $enrollment->modality_id) {
            return back()->withErrors(['to_modality_id' => 'You are already enrolled in that modality.']);
        }

        $alreadyPending = ModalityRequest::query()
            ->where('student_enrollment_id', $enrollment->id)
            ->where('status', ModalityRequest::STATUS_PENDING)
            ->exists();
        if ($alreadyPending) {
            return back()->withErrors(['to_modality_id' => 'You already have a pending modality request.']);
        }

        ModalityRequest::create([
            'school_id' => $enrollment->school_id,
            'student_id' => $student->id,
            'student_enrollment_id' => $enrollment->id,
            'from_modality_id' => $enrollment->modality_id,
            'to_modality_id' => $option->id,
            'reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('success', 'Modality request submitted. The registrar will review it.');
    }

    /** The school's offered modalities; every modality when none are configured. */
    private function options(int $schoolId)
    {
        $enabled = Modality::query()
            ->join('school_modalities as sm', 'sm.modality_id', '=', 'modalities.id')
            ->where('sm.school_id', $schoolId)
            ->orderBy('modalities.name')
            ->get(['modalities.id', 'modalities.name', 'modalities.code']);

        return $enabled->isNotEmpty()
            ? $enabled
            : Modality::query()->orderBy('name')->get(['id', 'name', 'code']);
    }

    /** @return array{0: Student, 1: ?\App\Models\StudentEnrollment} */
    private function guard(): array
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->where('school_id', $user->school_id)->first();
        abort_unless($student, 403);

        // Basic ed never sees this feature (hidden in the sidebar too).
        abort_if($this->dashboard->isBasicEd($user), 404);

        return [$student, $this->dashboard->activeEnrollment($user)];
    }
}
