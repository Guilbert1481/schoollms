<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ComponentScore;
use App\Services\Grading\GradebookService;
use App\Services\Grading\GradingSchemeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Teacher gradebook (higher-ed classes). The teacher enters component scores for
 * a class they teach, saves them as a draft, and posts finals. All computation
 * and the write to student_enrollment_subjects live in GradebookService; this
 * controller is the thin web surface and the ownership guard.
 *
 * Basic-ed grade entry (report_card_grades, per learning area × period) is not
 * wired here yet: the data model has no teacher→learning-area assignment
 * (grade_level_subjects carries no teacher), so there is nothing to scope a
 * subject teacher's basic-ed gradebook to. The backend (GradebookService::post-
 * Node) is ready for when that assignment exists.
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
                $setting = $resolver->forClass($class);
                $context = [
                    'class' => $class,
                    'setting' => $setting,
                    'components' => $setting ? $setting->components : collect(),
                    'roster' => $setting ? $this->roster($class, $gradebook) : collect(),
                ];
            }
        }

        return view('teacher.gradebook.index', [
            'classes' => $classes,
            'context' => $context,
        ]);
    }

    public function draft(Request $request, GradebookService $gradebook)
    {
        [$class, $scores] = $this->validated($request);

        $gradebook->saveClassScores($class, $scores, Auth::id());

        return $this->back($class, 'Draft scores saved.');
    }

    public function post(Request $request, GradebookService $gradebook)
    {
        [$class, $scores] = $this->validated($request);

        // Persist the latest edits, then post the finals.
        $gradebook->saveClassScores($class, $scores, Auth::id());
        $results = $gradebook->postClass($class);

        $posted = collect($results)->filter(fn ($r) => $r->isComplete && $r->final !== null)->count();
        $held = count($results) - $posted;

        $message = "Posted {$posted} grade(s)."
            .($held > 0 ? " {$held} still incomplete and not posted." : '');

        return $this->back($class, $message);
    }

    /* ------------------------------------------------------------------ */

    /** Roster rows: student, current scores, computed preview, posted grade. */
    private function roster(ClassModel $class, GradebookService $gradebook): Collection
    {
        $preview = $gradebook->previewClass($class); // [studentId => GradeResult]

        // [studentId][componentId] => score
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
            ->map(fn ($r) => [
                'student_id' => (int) $r->student_id,
                'name' => trim("{$r->last_name}, {$r->first_name}"),
                'number' => $r->student_number,
                'scores' => $scoreIndex[(int) $r->student_id] ?? [],
                'preview' => $preview[(int) $r->student_id] ?? null,
                'posted_final' => $r->final_grade,
                'posted_status' => $r->status,
            ]);
    }

    /**
     * Validate the posted scores and re-establish the class from ownership.
     *
     * @return array{0: ClassModel, 1: array<int|string, array<int|string, mixed>>}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'scores' => ['nullable', 'array'],
            'scores.*' => ['array'],
            'scores.*.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $class = ClassModel::where('teacher_id', Auth::id())->findOrFail($data['class_id']);

        return [$class, $data['scores'] ?? []];
    }

    private function back(ClassModel $class, string $message)
    {
        return redirect()
            ->route('teacher.gradebook.index', ['class_id' => $class->id])
            ->with('status', $message);
    }
}
