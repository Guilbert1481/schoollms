<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ComponentScore;
use App\Models\GradeSetting;
use App\Services\Grading\GradeActivityService;
use App\Services\Grading\GradebookService;
use App\Services\Grading\GradingSchemeResolver;
use App\Services\Grading\StudentGradeLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Teacher gradebook. The teacher picks a class they teach, enters component
 * scores, saves a draft, and posts finals. Track-aware:
 *
 *   - higher ed → one final per class, posted to student_enrollment_subjects.
 *   - basic ed  → per learning area × grading period, posted to report_card_grades
 *                 for the students in THIS class's section (per-section teaching
 *                 assignment; a class is subject + section + teacher).
 *
 * All computation and the record write live in GradebookService; this is the
 * thin web surface and the ownership guard.
 */
class GradebookController extends Controller
{
    public function index(Request $request, GradingSchemeResolver $resolver, GradebookService $gradebook)
    {
        $userId = Auth::id();

        // subject/section lazy-loaded in the view.
        $classes = ClassModel::where('teacher_id', $userId)->orderBy('code')->get();

        $context = null;
        if ($request->filled('class_id')) {
            $class = $classes->firstWhere('id', (int) $request->query('class_id'));
            if ($class) {
                $context = $gradebook->classTrack($class) === 'basic'
                    ? $this->basicContext($class, $request, $gradebook)
                    : $this->higherContext($class, $resolver, $gradebook);
            }
        }

        return view('teacher.gradebook.index', [
            'classes' => $classes,
            'context' => $context,
        ]);
    }

    public function draft(Request $request, GradebookService $gradebook)
    {
        [$class, $scores, $period] = $this->validated($request);

        if ($gradebook->classTrack($class) === 'basic') {
            $gradebook->saveClassBasicScores($class, $period, $scores, Auth::id());
        } else {
            $gradebook->saveClassScores($class, $scores, Auth::id());
        }

        return $this->back($class, 'Draft scores saved.', $period);
    }

    public function post(Request $request, GradebookService $gradebook)
    {
        [$class, $scores, $period] = $this->validated($request);

        // Persist the latest edits, then post.
        if ($gradebook->classTrack($class) === 'basic') {
            $gradebook->saveClassBasicScores($class, $period, $scores, Auth::id());
            $results = $gradebook->postClassBasic($class, $period, Auth::id());
        } else {
            $gradebook->saveClassScores($class, $scores, Auth::id());
            $results = $gradebook->postClass($class);
        }

        $posted = collect($results)->filter(fn ($r) => $r->isComplete && $r->final !== null)->count();
        $held = count($results) - $posted;

        $message = "Posted {$posted} grade(s)."
            .($held > 0 ? " {$held} still incomplete and not posted." : '');

        return $this->back($class, $message, $period);
    }

    /**
     * Record a hand-entered graded item (the "Add Record" modal): one activity for
     * a component + each student's raw score, which recomputes the component.
     */
    public function storeRecord(Request $request, GradeActivityService $service)
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'grade_component_id' => ['required', 'integer'],
            'total_items' => ['required', 'integer', 'min:1', 'max:100000'],
            'title' => ['nullable', 'string', 'max:120'],
            // Bound by the school's configured grading-period count (Settings → Grades → Division).
            'period' => ['nullable', 'integer', 'min:1', 'max:'.GradeSetting::periodCountForSchool((int) (Auth::user()->school_id ?? 0))],
            'scores' => ['nullable', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $class = ClassModel::where('teacher_id', Auth::id())->findOrFail($data['class_id']);
        $period = isset($data['period']) ? (int) $data['period'] : null;

        // A raw score can never exceed the item count.
        foreach (($data['scores'] ?? []) as $raw) {
            if ($raw !== null && $raw !== '' && (float) $raw > (float) $data['total_items']) {
                return back()
                    ->withErrors(['scores' => "A raw score can't exceed the total items ({$data['total_items']})."])
                    ->withInput();
            }
        }

        $activity = $service->record(
            $class,
            (int) $data['grade_component_id'],
            (int) $data['total_items'],
            $data['title'] ?? null,
            $data['scores'] ?? [],
            $period,
            Auth::id(),
        );

        return $activity
            ? $this->back($class, 'Record saved.', $period)
            : $this->back($class, 'Could not save — that component is not in this class\'s grading scheme.', $period);
    }

    /** A student's detailed grade ledger, rendered as the right-drawer partial. */
    public function ledger(Request $request, StudentGradeLedger $ledger)
    {
        $class = ClassModel::where('teacher_id', Auth::id())->findOrFail((int) $request->query('class_id'));
        $studentId = (int) $request->query('student_id');
        $period = $request->query('period') !== null ? max(1, min(4, (int) $request->query('period'))) : null;

        $student = DB::table('students')->where('id', $studentId)->first(['first_name', 'last_name', 'student_number']);

        return view('teacher.gradebook.partials.ledger', [
            'data' => $ledger->forStudent($class, $studentId, $period),
            'class' => $class,
            'studentId' => $studentId,
            'studentName' => $student ? trim("{$student->last_name}, {$student->first_name}") : 'Student',
            'studentNumber' => $student->student_number ?? '',
        ]);
    }

    /* ------------------------------------------------------------------ */

    private function higherContext(ClassModel $class, GradingSchemeResolver $resolver, GradebookService $gradebook): array
    {
        $setting = $resolver->forClass($class);

        return [
            'class' => $class,
            'track' => 'higher',
            'period' => null,
            'has_scheme' => (bool) $setting,
            'components' => $setting ? $setting->components : collect(),
            'roster' => $setting ? $this->higherRoster($class, $gradebook) : collect(),
        ];
    }

    private function basicContext(ClassModel $class, Request $request, GradebookService $gradebook): array
    {
        $period = $this->period($request);
        $ctx = $gradebook->basicContext($class);

        return [
            'class' => $class,
            'track' => 'basic',
            'period' => $period,
            'has_scheme' => (bool) $ctx,
            'components' => $ctx ? $ctx['setting']->components : collect(),
            'roster' => $ctx ? $this->basicRoster($class, $period, $ctx['node_id'], $gradebook) : collect(),
        ];
    }

    /** Higher-ed roster from student_enrollment_subjects. */
    private function higherRoster(ClassModel $class, GradebookService $gradebook): Collection
    {
        $preview = $gradebook->previewClass($class);

        $scoreIndex = [];
        foreach (ComponentScore::where('class_id', $class->id)->get(['student_id', 'grade_component_id', 'score']) as $sr) {
            $scoreIndex[(int) $sr->student_id][(int) $sr->grade_component_id] = $sr->score;
        }

        return DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
            ->join('students as s', 's.id', '=', 'e.student_id')
            ->where('ses.class_id', $class->id)
            ->orderBy('s.last_name')->orderBy('s.first_name')
            ->get(['s.id as student_id', 's.first_name', 's.last_name', 's.student_number', 'ses.final_grade', 'ses.status'])
            ->map(fn ($r) => $this->row($r, $scoreIndex, $preview, $r->final_grade, $r->status));
    }

    /** Basic-ed roster: this section's students, scored per node + subject + period. */
    private function basicRoster(ClassModel $class, int $period, int $nodeId, GradebookService $gradebook): Collection
    {
        $preview = $gradebook->previewClassBasic($class, $period);

        $scoreIndex = [];
        foreach (ComponentScore::where('education_node_id', $nodeId)->where('subject_id', $class->subject_id)->where('grading_period', $period)
            ->get(['student_id', 'grade_component_id', 'score']) as $sr) {
            $scoreIndex[(int) $sr->student_id][(int) $sr->grade_component_id] = $sr->score;
        }

        $posted = DB::table('report_card_grades')
            ->where('education_node_id', $nodeId)->where('subject_id', $class->subject_id)->where('grading_period', $period)
            ->pluck('final_grade', 'student_id');

        return DB::table('student_enrollments as e')
            ->join('students as s', 's.id', '=', 'e.student_id')
            ->where('e.section_id', $class->section_id)
            ->where('e.education_node_id', $nodeId)
            ->whereIn('e.status', ['enrolled', 'provisionally_enrolled'])
            ->orderBy('s.last_name')->orderBy('s.first_name')
            ->get(['s.id as student_id', 's.first_name', 's.last_name', 's.student_number'])
            ->map(fn ($r) => $this->row($r, $scoreIndex, $preview, $posted[(int) $r->student_id] ?? null, null));
    }

    /**
     * @param  array<int, array<int, mixed>>  $scoreIndex
     * @param  array<int, mixed>  $preview
     */
    private function row(object $r, array $scoreIndex, array $preview, $postedFinal, $postedStatus): array
    {
        return [
            'student_id' => (int) $r->student_id,
            'name' => trim("{$r->last_name}, {$r->first_name}"),
            'number' => $r->student_number,
            'scores' => $scoreIndex[(int) $r->student_id] ?? [],
            'preview' => $preview[(int) $r->student_id] ?? null,
            'posted_final' => $postedFinal,
            'posted_status' => $postedStatus,
        ];
    }

    /**
     * @return array{0: ClassModel, 1: array<int|string, array<int|string, mixed>>, 2: int}
     */
    private function validated(Request $request): array
    {
        // Bound the period by the school's configured count so grades can't be
        // posted to an ordinal the school doesn't use (Settings → Grades → Division).
        $maxPeriod = GradeSetting::periodCountForSchool((int) (Auth::user()->school_id ?? 0));

        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'period' => ['nullable', 'integer', 'min:1', 'max:'.$maxPeriod],
            'scores' => ['nullable', 'array'],
            'scores.*' => ['array'],
            'scores.*.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $class = ClassModel::where('teacher_id', Auth::id())->findOrFail($data['class_id']);

        return [$class, $data['scores'] ?? [], (int) ($data['period'] ?? 1)];
    }

    private function period(Request $request): int
    {
        $maxPeriod = GradeSetting::periodCountForSchool((int) (Auth::user()->school_id ?? 0));

        return max(1, min($maxPeriod, (int) $request->query('period', 1)));
    }

    private function back(ClassModel $class, string $message, ?int $period = null)
    {
        return redirect()
            ->route('teacher.gradebook.index', array_filter(['class_id' => $class->id, 'period' => $period]))
            ->with('status', $message);
    }
}
