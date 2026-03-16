<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QuestionBankController extends Controller
{
    /**
     * Save MCQ questions into the Question Bank
     * (questions + choices tables)
     */
    public function saveMcq(Request $request)
    {
        $request->validate([
            'questions' => 'required|array|min:1',
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->questions as $q) {

                $questionId = DB::table('questions')->insertGetId([
                    'school_id'     => Auth::user()->school_id ?? 1,
                    'teacher_id'    => Auth::id(),
                    'subject_id'    => $request->subject_id,
                    'topic_id'      => $request->topic_id,
                    'lesson_id'     => $request->lesson_id,
                    'competency_id' => $request->competency_id,
                    'test_id'       => null, // QUESTION BANK (important)
                    'keyword'       => $q['keyword'],
                    'question_text' => $q['question_text'],
                    'question_type' => 'mcq',
                    'points'        => $q['points'] ?? 1,
                    'status'        => 'active',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                foreach ($q['choices'] as $index => $choice) {
                    DB::table('choices')->insert([
                        'question_id' => $questionId,
                        'choice_text' => $choice,
                        'is_correct'  => $index === $q['correct_index'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'MCQ saved to Question Bank'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
