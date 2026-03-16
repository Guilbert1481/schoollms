<?php

namespace App\Http\Controllers\Teacher\Question;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


use App\Models\Question;
use App\Models\Choice;

class McqController extends Controller
{
    /**
     * MCQ BUILDER ENTRY
     */
    public function builder()
    {
        return $this->index();
    }

    /**
     * MCQ PAGE (GET)
     */
    public function index()
    {logger('QB session type: ' . session('qb.question_type'));
        if (!$this->hasMetadata()) {
            return redirect()
                ->route('teacher.question.metadata')
                ->withErrors('Please complete question metadata first.');
        }

        $meta = session('qb');

        return view(
            'teacher.question-bank.question-types.mcq',
            compact('meta')
        );
    }

    /**
     * SAVE MCQ (BATCH)
     */
    public function saveMcq(Request $request)
{
    if (!$this->hasMetadata()) {
        return response()->json([
            'success' => false,
            'error'   => 'Metadata session missing or expired.',
        ], 422);
    }

    $request->validate([
        'questions'                         => 'required|array|min:1',
        'questions.*.question_text'         => 'required|string',
        'questions.*.keyword'               => 'nullable|string',
        'questions.*.difficulty'            => 'required|in:average,advanced',
        'questions.*.choices'               => 'required|array|min:2',
        'questions.*.choices.*'             => 'required|string',
        'questions.*.explanation'           => 'nullable|string',
        'questions.*.correct_index'         => 'required|integer|min:0',
    ]);

    $requiredSessionKeys = [
        'school_id',
        'teacher_id',
        'subject_id',
        'topic_id',
        'lesson_id',
        'competency_id',
        'academic_level_id',
    ];

    // Check that all required session values exist
    foreach ($requiredSessionKeys as $key) {
        if (!session()->has("qb.$key")) {
            return response()->json([
                'success' => false,
                'error'   => "Missing required metadata: $key",
            ], 422);
        }
    }

    // Retrieve session
    $qb = session('qb');

    // Fetch the academic level by ID
    $academicLevelId = $qb['academic_level_id'];
    $academicLevel = \App\Models\AcademicLevel::find($academicLevelId);
    if (!$academicLevel) {
        return response()->json([
            'success' => false,
            'error' => 'Selected academic level not found in database.',
        ], 422);
    }
    $academicLevelId = $academicLevel->id;

    DB::beginTransaction();

    try {
        foreach ($request->questions as $q) {

            $schoolId = auth()->check()
                ? auth()->user()->school_id
                : null;

            if (!$schoolId) {
                throw new \Exception('School ID missing for authenticated user.');
            }

            // CREATE QUESTION
            logger()->info('QB SESSION', $qb);

            $question = \App\Models\Question::create([
                'school_id'         => $schoolId,
                'teacher_id'        => auth()->id(),
                'subject_id'        => $qb['subject_id'],
                'topic_id'          => $qb['topic_id'],
                'lesson_id'         => $qb['lesson_id'],
                'competency_id'     => $qb['competency_id'] ?? null,
                'academic_level_id' => $qb['academic_level_id'],
                'question_type'     => 'multiple_choice',
                'question_text'     => $q['question_text'],
                'difficulty'        => $q['difficulty'],
                'keyword'           => $q['keyword'] ?? null,
                'explanation'       => $q['explanation'] ?? null,
            ]);

            // SAVE CHOICES
            foreach ($q['choices'] as $index => $choiceText) {
                $question->choices()->create([
                    'choice_text' => $choiceText,
                    'is_correct'  => $index === $q['correct_index'],
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success'  => true,
            'redirect' => route('teacher.dashboard'),
        ]);

    } catch (\Throwable $e) {

        DB::rollBack();

        logger()->error('MCQ SAVE FAILED', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'error'   => 'Failed to save questions.',
        ], 500);
    }
}

    /**
     * CLEAR SESSION
     */
    public function clearQuestionSession()
    {
        $this->clearQuestionSessionInternal();
        return response()->json(['cleared' => true]);
    }

    /**
     * INTERNAL HELPERS
     */
    private function hasMetadata(): bool
    {
        return session()->has([
            'qb.subject_id',
            'qb.topic_id',
            'qb.lesson_id',
            'qb.competency_id',
            'qb.academic_level_id',
            'qb.question_type',
        ]);
    }

    private function clearQuestionSessionInternal(): void
    {
        session()->forget([
            'qb.subject_id',
            'qb.topic_id',
            'qb.lesson_id',
            'qb.competency_id',
            'qb.academic_level_id',
            'qb.question_type',
        ]);
    }
}
