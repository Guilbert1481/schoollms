<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Services\Grading\GradebookService;
use App\Services\Grading\GradingSchemeResolver;
use App\Services\Grading\HomeworkComponentFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Teacher homework. Mirrors the gradebook/attendance pattern: the teacher picks
 * a class they teach and manages homework tied to it, so homework works across
 * every subject and section the teacher is assigned. Students submit; the
 * teacher scores each submission. Files live on the private disk and are served
 * only to the owning teacher.
 */
class HomeworkController extends Controller
{
    public function index(Request $request, GradingSchemeResolver $resolver, GradebookService $gradebook)
    {
        $userId = Auth::id();
        $classes = ClassModel::where('teacher_id', $userId)->orderBy('code')->get();

        $context = null;
        if ($request->filled('class_id')) {
            $class = $classes->firstWhere('id', (int) $request->query('class_id'));
            if ($class) {
                // The class's grade components — a homework can be tagged to one so
                // grading it feeds that component of the gradebook.
                $track = $gradebook->classTrack($class);
                if ($track === 'basic') {
                    $ctx = $gradebook->basicContext($class);
                    $components = $ctx ? $ctx['setting']->components : collect();
                } else {
                    $setting = $resolver->forClass($class);
                    $components = $setting ? $setting->components : collect();
                }

                $context = [
                    'class' => $class,
                    'track' => $track,
                    'components' => $components,
                    'homework' => Homework::where('class_id', $class->id)->withCount('submissions')->orderByDesc('id')->get(),
                ];
            }
        }

        return view('teacher.homework.index', compact('classes', 'context'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:200'],
            'instructions' => ['nullable', 'string'],
            'points' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'due_at' => ['nullable', 'date'],
            'grade_component_id' => ['nullable', 'integer'],
            'grading_period' => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);

        $class = ClassModel::where('teacher_id', Auth::id())->findOrFail($data['class_id']);

        Homework::create([
            'class_id' => $class->id,
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'points' => $data['points'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'grade_component_id' => $this->validComponentId($class, $data['grade_component_id'] ?? null),
            'grading_period' => $data['grading_period'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Homework posted.');
    }

    public function show(Homework $homework)
    {
        $this->authorizeOwner($homework);

        $submissions = $homework->submissions()
            ->with('student:id,first_name,last_name,student_number')
            ->get();

        return view('teacher.homework.show', compact('homework', 'submissions'));
    }

    public function grade(Request $request, Homework $homework, HomeworkComponentFeed $feed)
    {
        $this->authorizeOwner($homework);

        $data = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.score' => ['nullable', 'numeric', 'min:0'],
            'grades.*.feedback' => ['nullable', 'string'],
        ]);

        foreach ($data['grades'] as $submissionId => $g) {
            $submission = HomeworkSubmission::where('homework_id', $homework->id)->find((int) $submissionId);
            if (! $submission) {
                continue;
            }

            $submission->update([
                'score' => ($g['score'] === '' || $g['score'] === null) ? null : (float) $g['score'],
                'feedback' => $g['feedback'] ?? null,
                'graded_at' => now(),
                'graded_by' => Auth::id(),
            ]);
        }

        // If this homework is tagged to a grade component, roll the new scores
        // into the gradebook automatically.
        $feed->sync($homework);

        return back()->with('success', 'Grades saved.');
    }

    public function downloadFile(HomeworkSubmission $submission)
    {
        $this->authorizeOwner($submission->homework);

        abort_unless($submission->file_path && Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->file_name ?: 'submission');
    }

    /* ------------------------------------------------------------------ */

    /** A teacher may only touch homework for a class they teach. */
    private function authorizeOwner(Homework $homework): void
    {
        $ownerId = DB::table('classes')->where('id', $homework->class_id)->value('teacher_id');
        abort_unless((int) $ownerId === (int) Auth::id(), 403);
    }

    /** Accept a component tag only if it is a real grade component in this school. */
    private function validComponentId(ClassModel $class, ?int $componentId): ?int
    {
        if (! $componentId) {
            return null;
        }

        $exists = DB::table('grade_components as gc')
            ->join('grading_settings as gs', 'gs.id', '=', 'gc.grading_setting_id')
            ->where('gc.id', $componentId)->where('gs.school_id', $class->school_id)
            ->exists();

        return $exists ? $componentId : null;
    }
}
