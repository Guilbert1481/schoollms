<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\Tests\OnlineTestAttemptService;
use App\Services\Tests\OnlineTestGradingService;
use App\Services\Tests\TestAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The student side of online tests: a pre-test start page, the take screen, per-answer
 * autosave, and submit. Every action re-derives availability and enrolment server-side
 * and enforces the server-authoritative deadline — the browser countdown is only a hint.
 */
class AssessmentController extends Controller
{
    public function __construct(
        private OnlineTestAttemptService $attempts,
        private OnlineTestGradingService $grading,
        private TestAvailability $availability,
    ) {}

    /** Pre-test start page: what the student is about to take, then a Start/Resume button. */
    public function start(Test $test)
    {
        [$student] = $this->guard($test);

        $status = $this->availability->status($test);
        $active = $this->attempts->activeAttempt($test, $student->id);
        $used = $this->attempts->attemptsUsed($test, $student->id);
        $allowed = $this->availability->attemptsAllowed($test);

        return view('student.assessments.start', [
            'test' => $test,
            'status' => $status,
            'opensAt' => $this->availability->opensAt($test),
            'closesAt' => $this->availability->closesAt($test),
            'itemCount' => $test->testQuestions()->count(),
            'durationMinutes' => $test->settings->duration_minutes ?? $test->settings->timer_minutes,
            'attemptsLeft' => max(0, $allowed - $used),
            'hasActive' => (bool) $active,
        ]);
    }

    /** Begin (or resume) the attempt, then hand off to the take screen. */
    public function begin(Test $test)
    {
        [$student] = $this->guard($test);

        if (! $this->attempts->activeAttempt($test, $student->id)) {
            abort_unless($this->availability->isOpen($test), 403, 'This test is not open for taking right now.');
            abort_if($this->attempts->attemptsUsed($test, $student->id) >= $this->availability->attemptsAllowed($test),
                403, 'You have used all your attempts for this test.');
        }

        $this->attempts->startOrResume($test, $student->id, (int) $student->school_id);

        return redirect()->route('student.assessments.take', $test);
    }

    /** The take screen for the student's in-progress attempt. */
    public function take(Test $test)
    {
        [$student] = $this->guard($test);

        $attempt = $this->attempts->activeAttempt($test, $student->id);
        if (! $attempt) {
            return redirect()->route('student.assessments.start', $test);
        }
        if ($this->enforceDeadline($attempt)) {
            return redirect()->route('student.assessments.result', $test);
        }

        $remaining = $attempt->deadline_at ? max(0, now()->diffInSeconds($attempt->deadline_at, false)) : null;

        return view('student.assessments.take', [
            'test' => $test,
            'attempt' => $attempt,
            'sections' => $this->attempts->sections($attempt),
            'remainingSeconds' => $remaining,
        ]);
    }

    /** Autosave one answer (XHR). Enforces the deadline before accepting the write. */
    public function answer(Request $request, TestAttempt $attempt): JsonResponse
    {
        $this->guardAttempt($attempt);

        if ($this->enforceDeadline($attempt)) {
            return response()->json(['expired' => true, 'redirect' => route('student.assessments.result', $attempt->test)], 409);
        }

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'response' => ['nullable', 'array'],
            'marked_for_review' => ['sometimes', 'boolean'],
        ]);

        $answer = $attempt->answers()->where('question_id', $data['question_id'])->first();
        abort_unless($answer, 404, 'That question is not part of this attempt.');

        $answer->update([
            'response' => $data['response'] ?? null,
            'marked_for_review' => (bool) ($data['marked_for_review'] ?? $answer->marked_for_review),
            'answered_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /** Submit the attempt for grading. */
    public function submit(TestAttempt $attempt)
    {
        $this->guardAttempt($attempt);

        $this->grading->submit($attempt, auto: $this->enforceDeadline($attempt));

        return redirect()->route('student.assessments.result', $attempt->test)
            ->with('success', 'Your test has been submitted.');
    }

    /** Result page — the student's latest attempt, with the score gated by show_results. */
    public function result(Test $test)
    {
        [$student] = $this->guard($test);

        $attempt = TestAttempt::where('test_id', $test->id)->where('student_id', $student->id)
            ->latest('id')->first();
        abort_unless($attempt, 404);

        return view('student.assessments.result', [
            'test' => $test,
            'attempt' => $attempt,
            'showScore' => $this->scoreVisible($test),
        ]);
    }

    // --- guards -------------------------------------------------------------

    /** @return array{0: Student} */
    private function guard(Test $test): array
    {
        $student = Student::where('user_id', Auth::id())->first();
        abort_unless($student, 403);
        abort_unless((int) $test->school_id === (int) $student->school_id, 404);
        abort_unless($this->availability->isOnline($test), 404, 'This is not an online test.');
        abort_unless($test->class_id && $this->enrolledIn($student, (int) $test->class_id), 403);

        return [$student];
    }

    private function guardAttempt(TestAttempt $attempt): void
    {
        $student = Student::where('user_id', Auth::id())->first();
        abort_unless($student, 403);
        abort_unless((int) $attempt->student_id === (int) $student->id, 403);
        abort_unless((int) $attempt->school_id === (int) $student->school_id, 404);
    }

    /** Auto-submit a sitting that has run past its server deadline. Returns true if it did. */
    private function enforceDeadline(TestAttempt $attempt): bool
    {
        if ($attempt->isExpired()) {
            $this->grading->submit($attempt, auto: true);

            return true;
        }

        return false;
    }

    private function scoreVisible(Test $test): bool
    {
        $mode = $test->settings->show_results ?? 'after_exam';
        if ($mode === 'never') {
            return false;
        }
        if ($mode === 'immediate') {
            return true;
        }
        // after_exam: once the window closes (duration tests have none → show once submitted).
        $closes = $this->availability->closesAt($test);

        return $closes === null || $closes->isPast();
    }

    /** Every class the student belongs to — higher-ed via subject enrolments, basic ed via section. */
    private function enrolledIn(Student $student, int $classId): bool
    {
        $higher = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
            ->where('e.student_id', $student->id)->pluck('ses.class_id');

        $sectionIds = DB::table('student_enrollments')
            ->where('student_id', $student->id)
            ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
            ->pluck('section_id')->filter();
        $basic = DB::table('classes')->whereIn('section_id', $sectionIds)->pluck('id');

        return $higher->merge($basic)->map(fn ($i) => (int) $i)->contains($classId);
    }
}
