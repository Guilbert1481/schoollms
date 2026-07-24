<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\Games\SnakeLadderAttemptService;
use App\Services\Tests\OnlineTestAttemptService;
use App\Services\Tests\OnlineTestGradingService;
use App\Services\Tests\TestAvailability;
use App\Support\StudentTestAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The student side of a Quiz Snakes & Ladders sitting — the BOARD-GAME
 * delivery of an ordinary online test attempt. Availability, enrolment,
 * attempt limits, and the server deadline use the exact same guards as the
 * standard take flow and Speed Dash (StudentTestAccess + TestAvailability);
 * grading is the shared pipeline. Game rules live in SnakeLadderAttemptService.
 */
class SnakeLadderController extends Controller
{
    public function __construct(
        private SnakeLadderAttemptService $game,
        private OnlineTestAttemptService $attempts,
        private OnlineTestGradingService $grading,
        private TestAvailability $availability,
        private StudentTestAccess $access,
    ) {}

    /**
     * The board screen. Starts a fresh attempt (same limit checks as the
     * standard begin) or resumes the in-progress one — a refresh lands the
     * student back mid-game with position, rolls, and locked answers intact.
     */
    public function play(Test $test)
    {
        $student = $this->access->guardTest($test);
        abort_unless($this->game->isEnabled($test), 404, 'This test is not playable as Quiz Snakes & Ladders.');

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

        // Real photo when the student has one; friendly placeholder until then.
        $avatar = $student->photo_path
            ? asset('storage/'.$student->photo_path)
            : ($student->user?->profile_photo
                ? asset('storage/'.$student->user->profile_photo)
                : asset('images/games/avatar-placeholder.svg'));

        return view('student.assessments.board', [
            'test' => $test,
            'attempt' => $attempt,
            'payload' => $this->game->payload($attempt),
            'remainingSeconds' => $remaining,
            'studentName' => trim(($student->preferred_name ?: $student->first_name).' '.$student->last_name),
            'avatarUrl' => $avatar,
        ]);
    }

    /** Grade one question (XHR). Idempotent — see SnakeLadderAttemptService::answer(). */
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
        ]);

        return response()->json($this->game->answer($attempt, (int) $data['question_id'], (int) $data['choice_id']));
    }

    /**
     * Roll the dice for a correctly-answered question (XHR). The client sends
     * only the question id — dice value and movement are server-authoritative
     * and idempotent per question.
     */
    public function roll(Request $request, TestAttempt $attempt): JsonResponse
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
        ]);

        return response()->json($this->game->roll($attempt, (int) $data['question_id']));
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
            ->with('success', 'Your game is complete — your test has been submitted.');
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
