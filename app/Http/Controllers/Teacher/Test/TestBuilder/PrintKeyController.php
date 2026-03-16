<?php

namespace App\Http\Controllers\Teacher\Test\TestBuilder;

use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Http\Request;
use App\Traits\BuildsTestSections;

class PrintKeyController extends Controller
{
    use BuildsTestSections;

    public function printAnswerKey(Test $test)
    {
        $test->load(['settings', 'subject', 'teacher', 'class.semester']);

        $questions = $test->testQuestions()->with(['question.choices'])->get();

        foreach ($questions as $q) {
            $q->print_type = match ($q->question->question_type) {
                'multiple_choice'     => 'mcq',
                'true_false'          => 'true_false',
                'mtf'                 => 'mtf',
                'matching'            => 'matching',
                'fib'                 => 'fib',
                'identification'      => 'identification',
                'enumeration'         => 'enumeration',
                'essay'               => 'essay',
                default               => $q->question->question_type,
            };
        }

        $order = [
            'true_false'     => 1,
            'mtf'            => 2,
            'mcq'            => 3,
            'matching'       => 4,
            'fib'            => 5,
            'identification' => 6,
            'enumeration'    => 7,
            'essay'          => 8,
        ];

        $questions = $questions->sortBy(fn($q) => $order[$q->print_type] ?? 99)->values();

        $sectionTitles = [
            'true_false'     => 'True or False',
            'mtf'            => 'Modified True or False',
            'mcq'            => 'Multiple Choice',
            'matching'       => 'Matching Type',
            'fib'            => 'Fill in the Blanks',
            'identification' => 'Identification',
            'enumeration'    => 'Enumeration',
            'essay'          => 'Essay',
        ];

        $directions = [
            'true_false'     => 'Write TRUE if the statement is correct and FALSE if it is not.',
            'mtf'            => 'Write TRUE if the statement is correct; if FALSE, underline the word that makes it incorrect and write the correct answer on the blank.',
            'mcq'            => 'Encircle the letter of the correct answer.',
            'matching'       => 'Match Column A with Column B.',
            'fib'            => 'Write the correct answer in the blank.',
            'identification' => 'Identify what is being described.',
            'enumeration'    => 'Enumerate the required items.',
            'essay'          => 'Answer the question in essay form.',
        ];

        $grouped = $questions->groupBy('print_type');

        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'];
        $sections = [];
        $index = 0;

        foreach ($sectionTitles as $type => $title) {
            if (!isset($grouped[$type])) continue;

            $sectionQuestions = $grouped[$type]->sortBy('id')->values();

            // You don't need to compute points/directions for answer key, but they can be included if needed for consistency

            // Custom handling for matching type (if your answer key blade expects 'prompts' and 'shuffledAnswers')
            if ($type === 'matching') {
                // Combine all choices for matching (Column B: unique by choice_text, keeps order)
                $columnB = $sectionQuestions
                    ->flatMap(fn($q) => $q->question->choices)
                    ->unique('choice_text')
                    ->values();

                $sections[] = [
                    'roman'      => $roman[$index],
                    'title'      => $title,
                    'questions'  => $sectionQuestions,
                    'columnB'    => $columnB, // Now available for every row in the view
                ];
            } else {
                $sections[] = [
                    'roman'      => $roman[$index],
                    'title'      => $title,
                    'questions'  => $sectionQuestions,
                ];
            }
            $index++;
        }

        // If you need $school, load it here or pass as null/default for branding.
        $school = $test->class->school ?? null;

        return view('teacher.tests.test-builder.answer_key', [
            'test' => $test,
            'sections' => $sections,
            'school' => $school,
        ]);
    }
}