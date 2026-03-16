<?php

namespace App\Http\Controllers\Teacher\Question;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Question;
use App\Models\Choice;

class FIBController extends Controller
{
    /**
     * FIB BUILDER ENTRY
     */
    public function builder()
    {
        return $this->index();
    }

    /**
     * FIB PAGE (GET)
     */
    public function index()
    {
        // Only allow access if metadata is set and question_type === 'fib'
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'fib') {
            return redirect()
                ->route('teacher.question.metadata')
                ->withErrors('Please complete question metadata first (and select Fill in the Blank as the type).');
        }

        $meta = session('qb');
        return view('teacher.question-bank.question-types.fib', compact('meta'));
    }

    /**
     * SAVE FIB QUESTIONS (BATCH)
     */
    public function save(Request $request)
    {
        // Only allow saving if session metadata is valid and question_type is 'fib'
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'fib') {
            return response()->json([
                'success' => false,
                'error'   => 'Metadata session missing or not set to FIB.',
            ], 422);
        }

        $request->validate([
            'questions'                         => 'required|array|min:1',
            'questions.*.question_text'         => 'required|string',
            'questions.*.difficulty'            => 'required|in:average,advanced',
            'questions.*.explanation'           => 'nullable|string',
            'questions.*.points'                => 'nullable|integer|min:1',
            'questions.*.answers'               => 'required|array|min:1',
            'questions.*.answers.*'             => 'required|string',
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

                // CREATE FIB QUESTION
                $question = Question::create([
                    'school_id'         => $schoolId,
                    'teacher_id'        => auth()->id(),
                    'subject_id'        => $qb['subject_id'],
                    'topic_id'          => $qb['topic_id'],
                    'lesson_id'         => $qb['lesson_id'],
                    'competency_id'     => $qb['competency_id'] ?? null,
                    'academic_level_id' => $qb['academic_level_id'],
                    'question_type'     => 'fib',
                    'question_text'     => $q['question_text'],
                    'difficulty'        => $q['difficulty'],
                    'explanation'       => $q['explanation'] ?? null,
                    'points'            => $q['points'] ?? null,
                ]);

                // CREATE Choices for every blank
                foreach ($q['answers'] as $blankIndex => $answer) {
                    Choice::create([
                        'question_id' => $question->id,
                        'choice_text' => $answer,
                        'is_correct'  => 1,
                        'meta'        => json_encode(['blank_index' => $blankIndex + 1]),
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

            logger()->error('FIB SAVE FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Failed to save FIB questions.',
                'details' => $e->getMessage(),
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