<?php

namespace App\Http\Controllers\Teacher\Question;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Question;

class IdentificationController extends Controller
{
    /**
     * IDENTIFICATION BUILDER ENTRY
     */
    public function builder()
    {
        return $this->index();
    }

    /**
     * IDENTIFICATION PAGE (GET)
     */
    public function index()
    {
        // Only allow access if metadata is set and question_type === 'identification'
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'identification') {
            return redirect()
                ->route('teacher.question.metadata')
                ->withErrors('Please complete question metadata first (and select Identification as the type).');
        }

        $meta = session('qb');

        return view('teacher.question-bank.question-types.identification', compact('meta'));
    }

    /**
     * SAVE IDENTIFICATION QUESTIONS (BATCH)
     */
    public function saveIdentification(Request $request)
    {
        // Only allow saving if session metadata is valid and question_type is 'identification'
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'identification') {
            return response()->json([
                'success' => false,
                'error'   => 'Metadata session missing or not set to Identification.',
            ], 422);
        }

        $request->validate([
            'questions'                         => 'required|array|min:1',
            'questions.*.question_text'         => 'required|string',
            'questions.*.keyword'               => 'nullable|string',
            'questions.*.difficulty'            => 'required|in:average,advanced',
            'questions.*.explanation'           => 'nullable|string',
            'questions.*.answer'                => 'required|string',
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

                // CREATE QUESTION
                $question = Question::create([
                    'school_id'         => $schoolId,
                    'teacher_id'        => auth()->id(),
                    'subject_id'        => $qb['subject_id'],
                    'topic_id'          => $qb['topic_id'],
                    'lesson_id'         => $qb['lesson_id'],
                    'competency_id'     => $qb['competency_id'] ?? null,
                    'academic_level_id' => $qb['academic_level_id'],
                    'question_type'     => 'identification',
                    'question_text'     => $q['question_text'],
                    'difficulty'        => $q['difficulty'],
                    'keyword'           => $q['keyword'] ?? null,
                    'explanation'       => $q['explanation'] ?? null,
                ]);

                // SAVE ANSWER (choices table: one row)
                $question->choices()->create([
                    'choice_text' => $q['answer'],
                    'is_correct'  => true,
                ]);
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'redirect' => route('teacher.dashboard'),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            logger()->error('Identification SAVE FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Failed to save Identification questions.',
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