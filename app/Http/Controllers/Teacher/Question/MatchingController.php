<?php

namespace App\Http\Controllers\Teacher\Question;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Question;

class MatchingController extends Controller
{
    /**
     * MATCHING BUILDER ENTRY
     */
    public function builder()
    {
        return $this->index();
    }

    /**
     * MATCHING PAGE (GET)
     */
    public function index()
    {
        // Make sure the metadata/session setup is correct for matching type
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'matching') {
            return redirect()
                ->route('teacher.question.metadata')
                ->withErrors('Please complete question metadata first (and select Matching as the type).');
        }

        $meta = session('qb');
        return view('teacher.question-bank.question-types.matching', compact('meta'));
    }

    /**
     * SAVE MATCHING QUESTIONS (BATCH)
     */
    public function saveMatching(Request $request)
    {
        // Ensure proper session/question_type
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'matching') {
            return response()->json([
                'success' => false,
                'error'   => 'Metadata session missing or not set to Matching.',
            ], 422);
        }

        $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.difficulty' => 'required|in:average,advanced',
            'questions.*.pairs' => 'required|array|min:1',
            'questions.*.pairs.*' => 'required|array|size:2',
            'questions.*.pairs.*.0' => 'required|string', // prompt
            'questions.*.pairs.*.1' => 'required|string', // answer
        ]);

        $qb = session('qb');
        DB::beginTransaction();

        try {
            foreach ($request->questions as $q) {
                $schoolId = auth()->check() ? auth()->user()->school_id : null;

                if (!$schoolId) {
                    throw new \Exception('School ID missing for authenticated user.');
                }

                $difficulty = $q['difficulty'];

                foreach ($q['pairs'] as $i => $pair) {
                    $prompt = $pair[0];
                    $answer = $pair[1];

                    // Each prompt/answer is its own question row
                    $question = Question::create([
                        'school_id'         => $schoolId,
                        'teacher_id'        => auth()->id(),
                        'subject_id'        => $qb['subject_id'],
                        'topic_id'          => $qb['topic_id'],
                        'lesson_id'         => $qb['lesson_id'],
                        'competency_id'     => $qb['competency_id'] ?? null,
                        'academic_level_id' => $qb['academic_level_id'],
                        'question_type'     => 'matching',
                        'question_text'     => $prompt,
                        'difficulty'        => $difficulty,
                    ]);

                    // Only one answer for this question
                    $question->choices()->create([
                        'choice_text' => $answer,
                        'is_correct' => 1,
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

            logger()->error('Matching SAVE FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Failed to save Matching questions.',
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