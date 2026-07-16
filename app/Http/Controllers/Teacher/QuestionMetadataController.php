<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\Subject;
use App\Services\Tests\LevelTreeResolver;
use App\Support\SubjectScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionMetadataController extends Controller
{
    public function __construct(private LevelTreeResolver $levels) {}

    /**
     * Show Question Metadata page
     */
    public function createInfo(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id ?? null;

        // Subjects are scoped to the current user's domain (school + academic/
        // training) AND, for teachers, to the subjects assigned to them.
        $subjects = SubjectScope::forTeacher(Subject::query(), $user)
            ->orderBy('name')
            ->get();

        $academicLevels = AcademicLevel::where('school_id', auth()->user()->school_id)
            ->orderBy('sequence_order')->get();

        // Education-structure tree + resolved academic_levels-per-node for the
        // single-select level cascade (shared with the Test Builder).
        $levelTree = $this->levels->tree();
        $levelsByNode = $this->levels->levelsByNode((int) $schoolId);

        $questionTypes = [
            'mcq' => 'Multiple Choice',
            'true_or_false' => 'True / False',
            'mtf' => 'Modified True / False',
            'identification' => 'Identification',
            'fib' => 'Fill in the Blank',
            'matching' => 'Matching Type',
            'enumeration' => 'Enumeration',
            'essay' => 'Essay',
        ];

        return view(
            'teacher.tests.question-metadata',
            compact('subjects', 'academicLevels', 'questionTypes', 'levelTree', 'levelsByNode')
        );
    }

    /**
     * Store Question Metadata (SESSION ONLY)
     */
    public function storeInfo(Request $request)
    {
        $user = $request->user();

        $subjectRule = ['required', 'integer'];

        // A teacher may only author questions for subjects assigned to them —
        // the dropdown is scoped, but never trust the client (hiding ≠ security).
        if ($user->role === 'teacher') {
            $assignedSubjectIds = SubjectScope::restrictToTeacher(Subject::query(), $user)
                ->pluck('id')
                ->all();
            $subjectRule[] = Rule::in($assignedSubjectIds);
        }

        $data = $request->validate([
            'subject_id' => $subjectRule,
            'topic_id' => 'nullable|integer',
            'lesson_id' => 'nullable|integer',
            'competency_id' => 'nullable|integer',
            'question_type' => 'required|string',
            'academic_level_id' => 'required|integer|exists:academic_levels,id',
        ], [
            'subject_id.in' => 'You are not assigned to that subject.',
        ]);

        // --- Normalize the question type for internal/builder use ---
        $normalizedType = match ($data['question_type']) {
            'true_or_false' => 'true_false',
            'mtf' => 'mtf',
            'identification' => 'identification',
            'fib' => 'fib',
            'matching' => 'matching',
            'enumeration' => 'enumeration',
            'essay' => 'essay',
            'mcq' => 'mcq',
            default => 'mcq',
        };

        // --- Save to session (always as normalized type) ---
        session([
            'qb.school_id' => $user->school_id,
            'qb.teacher_id' => $user->id,
            'qb.subject_id' => $data['subject_id'],
            'qb.topic_id' => $data['topic_id'] ?? null,
            'qb.lesson_id' => $data['lesson_id'] ?? null,
            'qb.competency_id' => $data['competency_id'] ?? null,
            'qb.academic_level_id' => $data['academic_level_id'],
            'qb.question_type' => $normalizedType,
        ]);

        // --- Route user to correct builder based on normalized type ---
        $targetUrl = match ($normalizedType) {
            'mcq' => route('mcq.builder'),
            'true_false' => route('tf.builder'),
            'mtf' => route('mtf.builder'),
            'identification' => route('identification.builder'),
            'fib' => route('fib.builder'),
            'matching' => route('matching.builder'),
            'enumeration' => route('enumeration.builder'),
            'essay' => route('essay.builder'),
            default => route('mcq.builder'),
        };

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $targetUrl,
                'message' => 'Redirecting to builder...',
            ]);
        }

        return redirect($targetUrl);
    }
}
