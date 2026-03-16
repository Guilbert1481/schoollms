<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionTypeTestSeeder extends Seeder
{
    public function run(): void
    {
        $testId = 169; // Cells – Parts of Cells
        $now = now();

        /*
        |--------------------------------------------------------------------------
        | MULTIPLE CHOICE (3)
        |--------------------------------------------------------------------------
        */
        $mcqQuestions = [
            'Which part of the cell controls all activities?',
            'Which organelle is known as the powerhouse of the cell?',
            'What part of the cell contains genetic material?',
        ];

        foreach ($mcqQuestions as $index => $text) {
            $questionId = DB::table('questions')->insertGetId([
                'test_id'       => $testId,
                'question_text' => $text,
                'question_type' => 'multiple_choice',
                'points'        => 1,
                'order'         => $index + 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            DB::table('choices')->insert([
                ['question_id' => $questionId, 'choice_text' => 'Nucleus',        'is_correct' => 1],
                ['question_id' => $questionId, 'choice_text' => 'Mitochondria',   'is_correct' => 0],
                ['question_id' => $questionId, 'choice_text' => 'Ribosome',       'is_correct' => 0],
                ['question_id' => $questionId, 'choice_text' => 'Cell wall',      'is_correct' => 0],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | TRUE / FALSE (3)
        |--------------------------------------------------------------------------
        */
        $tfQuestions = [
            'The nucleus controls the activities of the cell.',
            'All cells have a cell wall.',
            'Mitochondria produce energy for the cell.',
        ];

        foreach ($tfQuestions as $i => $text) {
            $questionId = DB::table('questions')->insertGetId([
                'test_id'       => $testId,
                'question_text' => $text,
                'question_type' => 'true_false',
                'points'        => 1,
                'order'         => $i + 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            DB::table('choices')->insert([
                ['question_id' => $questionId, 'choice_text' => 'True',  'is_correct' => 1],
                ['question_id' => $questionId, 'choice_text' => 'False', 'is_correct' => 0],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | MODIFIED TRUE / FALSE (3)
        |--------------------------------------------------------------------------
        */
        $mtfQuestions = [
            'The mitochondria is responsible for photosynthesis.',
            'The cell membrane protects and supports the cell.',
            'Ribosomes help in protein synthesis.',
        ];

        foreach ($mtfQuestions as $i => $text) {
            DB::table('questions')->insert([
                'test_id'       => $testId,
                'question_text' => $text,
                'question_type' => 'modified_true_false',
                'points'        => 1,
                'order'         => $i + 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | IDENTIFICATION (3)
        |--------------------------------------------------------------------------
        */
        $idQuestions = [
            'Organelle that controls cell activities.',
            'Organelle responsible for energy production.',
            'Jelly-like substance inside the cell.',
        ];

        foreach ($idQuestions as $i => $text) {
            DB::table('questions')->insert([
                'test_id'       => $testId,
                'question_text' => $text,
                'question_type' => 'identification',
                'points'        => 1,
                'order'         => $i + 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | FILL IN THE BLANK (3)
        |--------------------------------------------------------------------------
        */
        $fibQuestions = [
            'The _____ is the powerhouse of the cell.',
            'The _____ contains the DNA of the cell.',
            '_____ helps maintain the shape of the cell.',
        ];

        foreach ($fibQuestions as $i => $text) {
            DB::table('questions')->insert([
                'test_id'       => $testId,
                'question_text' => $text,
                'question_type' => 'fib',
                'points'        => 1,
                'order'         => $i + 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ENUMERATION (3)
        |--------------------------------------------------------------------------
        */
        $enumQuestions = [
            'Enumerate three parts of a plant cell.',
            'List three functions of the cell membrane.',
            'Name three organelles found in animal cells.',
        ];

        foreach ($enumQuestions as $i => $text) {
            DB::table('questions')->insert([
                'test_id'       => $testId,
                'question_text' => $text,
                'question_type' => 'enumeration',
                'points'        => 3,
                'order'         => $i + 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | MATCHING TYPE (3)
        |--------------------------------------------------------------------------
        */
        for ($i = 1; $i <= 3; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'test_id'       => $testId,
                'question_text' => 'Match the organelle with its function.',
                'question_type' => 'matching',
                'points'        => 3,
                'order'         => $i,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            DB::table('choices')->insert([
                ['question_id' => $questionId, 'choice_text' => 'Nucleus - Control center',        'is_correct' => 1],
                ['question_id' => $questionId, 'choice_text' => 'Mitochondria - Energy producer',  'is_correct' => 1],
                ['question_id' => $questionId, 'choice_text' => 'Ribosome - Protein synthesis',   'is_correct' => 1],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ESSAY (3)
        |--------------------------------------------------------------------------
        */
        $essayQuestions = [
            'Explain the function of the nucleus.',
            'Describe the importance of mitochondria.',
            'Why is the cell membrane important?',
        ];

        foreach ($essayQuestions as $i => $text) {
            DB::table('questions')->insert([
                'test_id'       => $testId,
                'question_text' => $text,
                'question_type' => 'essay',
                'points'        => 5,
                'order'         => $i + 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }
}
