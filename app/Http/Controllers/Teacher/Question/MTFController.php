<?php

namespace App\Http\Controllers\Teacher\Question;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Question;

class MTFController extends Controller
{
    public function builder()
    {
        return $this->index();
    }

    public function index()
    {
        // Only allow access if metadata is set and question_type === 'mtf'
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'mtf') {
            return redirect()
                ->route('teacher.question.metadata')
                ->withErrors('Please complete question metadata first (and select Modified True or False as the type).');
        }

        $meta = session('qb');
        return view('teacher.question-bank.question-types.mtf', compact('meta'));
    }

    public function saveMtf(Request $request)
    {
        // Only allow saving if session metadata is valid and question_type is 'mtf'
        if (!$this->hasMetadata() || $this->getQuestionType() !== 'mtf') {
            return response()->json([
                'success' => false,
                'error'   => 'Metadata session missing or not set to Modified True or False.',
            ], 422);
        }

        $request->validate([
            'questions'                             => 'required|array|min:1',
            'questions.*.question_text'             => 'required|string',
            'questions.*.keyword'                   => 'nullable|string',
            'questions.*.difficulty'                => 'required|in:average,advanced',
            'questions.*.explanation'               => 'nullable|string',
            'questions.*.answer'                    => 'required|in:true,false',
            'questions.*.incorrect_phrase'          => 'required_if:questions.*.answer,false|string|nullable',
            'questions.*.correct_phrase'            => 'required_if:questions.*.answer,false|string|nullable',
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
                    'question_type'     => 'mtf',
                    'question_text'     => $q['question_text'],
                    'difficulty'        => $q['difficulty'],
                    'keyword'           => $q['keyword'] ?? null,
                    'explanation'       => $q['explanation'] ?? null,
                ]);
                // SAVE ANSWER with correction meta if needed
                $meta = [];
                if ($q['answer'] === 'false') {
                    $meta['incorrect'] = $q['incorrect_phrase'];
                    $meta['correct']   = $q['correct_phrase'];
                }
                $question->choices()->create([
                    'choice_text' => ucfirst($q['answer']),
                    'is_correct'  => true,
                    'meta'        => $meta ? json_encode($meta) : null,
                ]);
            }
            DB::commit();
            return response()->json([
                'success'  => true,
                'redirect' => route('teacher.dashboard'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error('MTF SAVE FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => 'Failed to save Modified True or False questions.',
            ], 500);
        }
    }

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