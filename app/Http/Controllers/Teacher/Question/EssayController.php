<?php

namespace App\Http\Controllers\Teacher\Question;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Question;

class EssayController extends Controller
{
    /**
     * ESSAY BUILDER ENTRY
     */
    public function builder()
    {
        return $this->index();
    }

    /**
     * ESSAY PAGE (GET)
     */
    public function index()
    {
        // Only allow access if metadata is set and question_type === 'essay'
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'essay') {
            return redirect()
                ->route('teacher.question.metadata')
                ->withErrors('Please complete question metadata first (and select Essay as the type).');
        }

        $meta = session('qb');

        return view('teacher.question-bank.question-types.essay', compact('meta'));
    }

    /**
     * SAVE ESSAY QUESTIONS (BATCH)
     */
    public function saveEssay(Request $request)
    {
        // Only allow saving if session metadata is valid and question_type is 'essay'
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'essay') {
            return response()->json([
                'success' => false,
                'error'   => 'Metadata session missing or not set to Essay.',
            ], 422);
        }

        $request->validate([
            'questions'                         => 'required|array|min:1',
            'questions.*.question_text'         => 'required|string',
            'questions.*.difficulty'            => 'required|in:average,advanced',
            'questions.*.explanation'           => 'nullable|string',
            'questions.*.points'                => 'required|integer|min:1',
        ]);

        $qb = session('qb');

        DB::beginTransaction();

        try {
            foreach ($request->questions as $q) {
                $schoolId = auth()->check()
                    ? auth()->user()->school_id
                    : null;

                if (!$schoolId) {
                    throw new \Exception('School ID missing for authenticated user.');
                }

                // CREATE ESSAY QUESTION
                $question = Question::create([
                    'school_id'         => $schoolId,
                    'teacher_id'        => auth()->id(),
                    'subject_id'        => $qb['subject_id'],
                    'topic_id'          => $qb['topic_id'],
                    'lesson_id'         => $qb['lesson_id'],
                    'competency_id'     => $qb['competency_id'] ?? null,
                    'academic_level_id' => $qb['academic_level_id'],
                    'question_type'     => 'essay',
                    'question_text'     => $q['question_text'],
                    'difficulty'        => $q['difficulty'],
                    'explanation'       => $q['explanation'] ?? null,
                    'points'            => $q['points'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'redirect' => route('teacher.dashboard'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            logger()->error('Essay SAVE FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Failed to save Essay questions.',
            ], 500);
        }
    }

    /*
     * CLEAR SESSION
     */
    public function clearQuestionSession()
    {
        $this->clearQuestionSessionInternal();
        return response()->json(['cleared' => true]);
    }

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

    private function getQuestionType(): ?string
    {
        return session('qb.question_type', null);
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