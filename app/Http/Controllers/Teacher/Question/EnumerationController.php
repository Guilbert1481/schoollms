<?php

namespace App\Http\Controllers\Teacher\Question;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Question;

class EnumerationController extends Controller
{
    public function builder()
    {
        // show the enumeration builder view
        $meta = session('qb');
        return view('teacher.question-bank.question-types.enumeration', compact('meta'));
    }

    public function save(Request $request)
    {
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'enumeration') {
            return response()->json([
                'success' => false,
                'error'   => 'Metadata session missing or not set to Enumeration.',
            ], 422);
        }

        $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.keyword' => 'nullable|string',
            'questions.*.difficulty' => 'required|in:average,advanced',
            'questions.*.answers' => 'required|array|min:1',
            'questions.*.answers.*' => 'required|string'
        ]);

        $qb = session('qb');

        DB::beginTransaction();
        try {
            foreach ($request->questions as $q) {
                $schoolId = auth()->check() ? auth()->user()->school_id : null;
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
                    'question_type'     => 'enumeration',
                    'question_text'     => $q['question_text'],
                    'difficulty'        => $q['difficulty'],
                    'keyword'           => $q['keyword'] ?? null,
                ]);
                // SAVE ALL CORRECT ANSWERS
                foreach($q['answers'] as $ans) {
                    $question->choices()->create([
                        'choice_text' => $ans,
                        'is_correct'  => true,
                        'meta'        => null
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
            logger()->error('ENUMERATION SAVE FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => 'Failed to save Enumeration questions.',
            ], 500);
        }
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
}