<?php

namespace App\Http\Controllers\Teacher\Test\TestBuilder;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Traits\BuildsTestSections;

class PrintTestController extends Controller
{
    use BuildsTestSections;

    public function print(Test $test)
    {
        $test->load(['settings', 'subject', 'teacher', 'class.semester', 'class.section']);

        // Load all questions & choices
        $questions = $test->testQuestions()->with(['question.choices'])->get();

        // Map types for section grouping, and put each MCQ's choices into the
        // seed's arrangement order so the printed A–E labels match the answer key
        // and the frozen OMR snapshot (all three call TestArrangement::choiceOrder).
        foreach ($questions as $q) {
            $q->print_type = match ($q->question->question_type) {
                'multiple_choice' => 'mcq',
                'true_false' => 'true_false',
                'mtf' => 'mtf',
                'matching' => 'matching',
                'fib' => 'fib',
                'identification' => 'identification',
                'enumeration' => 'enumeration',
                'essay' => 'essay',
                default => $q->question->question_type,
            };

            if ($q->question->question_type === 'multiple_choice') {
                $q->question->setRelation('choices', \App\Support\TestArrangement::choiceOrder(
                    $test->print_seed,
                    $q->question->id,
                    $q->question->choices
                ));
            }
        }

        $order = [
            'true_false' => 1,
            'mtf' => 2,
            'mcq' => 3,
            'matching' => 4,
            'fib' => 5,
            'identification' => 6,
            'enumeration' => 7,
            'essay' => 8,
        ];

        $questions = $questions->sortBy(fn ($q) => $order[$q->print_type] ?? 99)->values();

        $sectionTitles = [
            'true_false' => 'True or False',
            'mtf' => 'Modified True or False',
            'mcq' => 'Multiple Choice',
            'matching' => 'Matching Type',
            'fib' => 'Fill in the Blanks',
            'identification' => 'Identification',
            'enumeration' => 'Enumeration',
            'essay' => 'Essay',
        ];

        $directions = [
            'true_false' => 'Write TRUE if the statement is correct and FALSE if it is not.',
            'mtf' => 'Write TRUE if the statement is correct; if FALSE, underline the word that makes it incorrect and write the correct answer on the blank.',
            'mcq' => 'Encircle the letter of the correct answer.',
            'matching' => 'Match Column A with Column B.',
            'fib' => 'Write the correct answer in the blank.',
            'identification' => 'Identify what is being described.',
            'enumeration' => 'Enumerate the required items.',
            'essay' => 'Answer the question in essay form.',
        ];

        $grouped = $questions->groupBy('print_type');

        $typePoints = \App\Models\TestQuestionTypePoint::where('test_id', $test->id)
            ->pluck('points', 'question_type')
            ->toArray();

        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'];
        $final = [];
        $index = 0;

        foreach ($sectionTitles as $type => $title) {
            if (! isset($grouped[$type])) {
                continue;
            }

            $sectionQuestions = $grouped[$type]
                ->sortBy(fn ($q) => \App\Support\TestArrangement::orderKey($test->print_seed, (int) $q->order))
                ->values();

            // --- SECTION POINTS LOGIC ---
            if ($type === 'enumeration') {
                // (points per item) × (number of choices)
                $sectionPoints = $sectionQuestions->sum(function ($q) use ($typePoints, $type) {
                    $perItem = $typePoints[$type] ?? 1;
                    $count = $q->question->choices ? $q->question->choices->count() : 0;

                    return $count * $perItem;
                });
            } elseif ($type === 'fib') {
                // (points per blank) × (number of blanks)
                $sectionPoints = $sectionQuestions->sum(function ($q) use ($typePoints, $type) {
                    $perBlank = $typePoints[$type] ?? 1;
                    $blanks = preg_match_all('/\{\{\s*blank\s*\}\}/i', $q->question->question_text ?? '', $matches);

                    return $blanks * $perBlank;
                });
            } else {
                // (points per question) × (number of questions in section)
                $pointsPerQuestion = $typePoints[$type] ?? 1;
                $sectionPoints = $sectionQuestions->count() * $pointsPerQuestion;
            }
            // --- END SECTION POINTS LOGIC ---

            // MCQ choice order was applied above (TestArrangement::choiceOrder).

            // Custom handling for matching type
            if ($type === 'matching') {
                $prompts = $sectionQuestions->map(function ($q) {
                    return $q->question->question_text;
                })->toArray();

                $answers = $sectionQuestions->map(function ($q) {
                    return optional($q->question->choices->first())->choice_text ?? '';
                })->values();

                // ✅ SHUFFLE HERE
                $shuffledAnswers = $answers->shuffle()->values()->toArray();

                $final[] = [
                    'roman' => $roman[$index],
                    'title' => $title,
                    'direction' => $directions[$type],
                    'prompts' => $prompts,
                    'shuffledAnswers' => $shuffledAnswers, // Note: name retained for template compatibility
                    'questions' => $sectionQuestions,
                    'points' => $sectionPoints,
                ];
            } else {
                $final[] = [
                    'roman' => $roman[$index],
                    'title' => $title,
                    'direction' => $directions[$type],
                    'questions' => $sectionQuestions,
                    'points' => $sectionPoints,
                ];
            }
            $index++;
        }

        return view('teacher.tests.test-builder.print', [
            'test' => $test,
            'sections' => $final,
            'printHeader' => \App\Support\TestPrintHeader::for($test),
        ]);
    }
}
