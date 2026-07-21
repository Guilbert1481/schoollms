<?php

namespace App\Http\Controllers\Teacher\Test;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\Tests\OnlineTestGradingService;
use Illuminate\Http\Request;

/**
 * Teacher → grade an online test's manual (essay) answers. Auto items are already
 * scored + frozen at submit; this screen scores the essays, which finalizes each
 * attempt (submitted → graded once none remain manual) and re-feeds the gradebook
 * via OnlineTestGradingService. It also surfaces the Phase-5 integrity log.
 *
 * Author-only, within the school: BelongsToSchool 404s another school's test at
 * bind time; the ownership check here mirrors TestManagement::publish().
 */
class GradingController extends Controller
{
    public function __construct(private OnlineTestGradingService $grading) {}

    /** Attempts for this test worth grading — those needing manual scoring first. */
    public function index(Test $test)
    {
        $this->authorizeTest($test);

        $rows = TestAttempt::where('test_id', $test->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->with('student')
            ->get()
            ->sortBy([['needs_manual', 'desc'], ['submitted_at', 'asc']])
            ->map(fn (TestAttempt $a) => [
                'id' => $a->id,
                'student' => $this->studentName($a),
                'status' => $a->status,
                'needsManual' => (bool) $a->needs_manual,
                'percentage' => $a->percentage,
                'raw' => $a->raw_score,
                'max' => $a->max_score,
                'submittedAt' => $a->submitted_at,
                'proctorTotal' => (int) ($a->meta['proctor']['total'] ?? 0),
            ])
            ->values();

        return view('teacher.tests.grading.index', ['test' => $test, 'rows' => $rows]);
    }

    /** The per-attempt screen: each essay answer with a score box, plus the integrity log. */
    public function show(Test $test, TestAttempt $attempt)
    {
        $this->authorizeTest($test);
        $this->authorizeAttempt($test, $attempt);

        $essays = $attempt->answers()
            ->where('question_type', 'essay')
            ->with('question')
            ->orderBy('id')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'question' => $a->question?->question_text ?? '(question removed)',
                'text' => is_array($a->response) ? (string) ($a->response['text'] ?? '') : '',
                'possible' => (float) $a->points_possible,
                'earned' => $a->points_earned === null ? null : (float) $a->points_earned,
                'needsManual' => (bool) $a->needs_manual,
            ])
            ->values();

        $auto = $attempt->answers()->where('question_type', '!=', 'essay')->get();

        return view('teacher.tests.grading.show', [
            'test' => $test,
            'attempt' => $attempt,
            'student' => $this->studentName($attempt),
            'essays' => $essays,
            'autoEarned' => (float) $auto->sum(fn ($a) => (float) ($a->points_earned ?? 0)),
            'autoMax' => (float) $auto->sum(fn ($a) => (float) $a->points_possible),
            'autoCount' => $auto->count(),
            'proctor' => $attempt->meta['proctor'] ?? null,
        ]);
    }

    /** Save the essay scores, then let the service finalize + re-feed. */
    public function store(Request $request, Test $test, TestAttempt $attempt)
    {
        $this->authorizeTest($test);
        $this->authorizeAttempt($test, $attempt);

        $data = $request->validate([
            'scores' => ['array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Blanks mean "not graded yet" — skip them so the answer stays provisional.
        // The service ignores any id that isn't a manual answer of this attempt.
        $scores = array_map('floatval', array_filter(
            $data['scores'] ?? [],
            fn ($v) => $v !== null && $v !== ''
        ));

        $count = $this->grading->gradeManualBatch($attempt, $scores, (int) auth()->id());

        return redirect()
            ->route('teacher.tests.grade.show', [$test, $attempt])
            ->with('success', $count === 1 ? '1 answer scored.' : $count.' answers scored.');
    }

    private function studentName(TestAttempt $attempt): string
    {
        $s = $attempt->student;

        return $s
            ? (trim(($s->first_name ?? '').' '.($s->last_name ?? '')) ?: 'Student #'.$attempt->student_id)
            : 'Student #'.$attempt->student_id;
    }

    private function authorizeTest(Test $test): void
    {
        abort_unless((int) $test->teacher_id === (int) auth()->id(), 403);
    }

    private function authorizeAttempt(Test $test, TestAttempt $attempt): void
    {
        abort_unless((int) $attempt->test_id === (int) $test->id, 404);
        abort_unless((int) $attempt->school_id === (int) $test->school_id, 404);
    }
}
