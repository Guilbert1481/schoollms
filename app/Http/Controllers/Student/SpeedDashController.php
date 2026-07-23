<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\Games\SpeedDashAttemptService;
use App\Services\Tests\OnlineTestAttemptService;
use App\Services\Tests\OnlineTestGradingService;
use App\Services\Tests\TestAvailability;
use App\Support\StudentTestAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The student side of a Quiz Speed Dash sitting — the GAME delivery of an
 * ordinary online test attempt. Availability, enrolment, attempt limits, and
 * the server deadline are enforced with the exact same guards as the standard
 * take flow (StudentTestAccess + TestAvailability); grading is the shared
 * pipeline. This controller only routes and validates — game rules live in
 * SpeedDashAttemptService.
 */
class SpeedDashController extends Controller
{
    public function __construct(
        private SpeedDashAttemptService $game,
        private OnlineTestAttemptService $attempts,
        private OnlineTestGradingService $grading,
        private TestAvailability $availability,
        private StudentTestAccess $access,
    ) {}

    /**
     * The game screen. Starts a fresh attempt (same limit checks as the
     * standard begin) or resumes the in-progress one — a refresh lands the
     * student back mid-run with hearts, streak, and locked answers intact.
     */
    public function play(Test $test)
    {
        $student = $this->access->guardTest($test);
        abort_unless($this->game->isEnabled($test), 404, 'This test is not playable as Speed Dash.');

        $eligibility = $this->game->eligibility($test);
        abort_unless($eligibility['ok'], 409, 'This test is not currently playable as a game. Please tell your teacher.');

        if (! $this->attempts->activeAttempt($test, $student->id)) {
            abort_unless($this->availability->isOpen($test), 403, 'This test is not open for taking right now.');
            abort_if(
                $this->attempts->attemptsUsed($test, $student->id) >= $this->availability->attemptsAllowed($test),
                403, 'You have used all your attempts for this test.'
            );
        }

        $attempt = $this->attempts->startOrResume($test, $student->id, (int) $student->school_id);
        if ($this->enforceDeadline($attempt)) {
            return redirect()->route('student.assessments.result', $test);
        }

        $remaining = $attempt->deadline_at ? max(0, (int) now()->diffInSeconds($attempt->deadline_at, false)) : null;

        return view('student.assessments.play', [
            'test' => $test,
            'attempt' => $attempt,
            'payload' => $this->game->payload($attempt),
            'remainingSeconds' => $remaining,
            'studentName' => trim(($student->preferred_name ?: $student->first_name).' '.$student->last_name),
        ]);
    }

    /** Grade one gate entry (XHR). Idempotent — see SpeedDashAttemptService::answer(). */
    public function answer(Request $request, TestAttempt $attempt): JsonResponse
    {
        $this->access->guardAttempt($attempt);

        if ($this->enforceDeadline($attempt)) {
            return response()->json([
                'expired' => true,
                'redirect' => route('student.assessments.result', $attempt->test),
            ], 409);
        }

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'choice_id' => ['required', 'integer'],
            'response_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
        ]);

        return response()->json($this->game->answer(
            $attempt,
            (int) $data['question_id'],
            (int) $data['choice_id'],
            isset($data['response_ms']) ? (int) $data['response_ms'] : null,
        ));
    }

    /**
     * Finish line: submit the attempt through the SAME grader as a standard
     * quiz. A timed-out sitting auto-submits with whatever was locked so far.
     */
    public function finish(TestAttempt $attempt)
    {
        $this->access->guardAttempt($attempt);

        $this->grading->submit($attempt, auto: $this->enforceDeadline($attempt));

        return redirect()->route('student.assessments.result', $attempt->test)
            ->with('success', 'Your run is complete — your test has been submitted.');
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
}
