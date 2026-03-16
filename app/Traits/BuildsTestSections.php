<?php

namespace App\Traits;

trait BuildsTestSections
{
    public function buildSections($test)
    {
        $sections = [];

        // Group questions by section title (if you have sections)
        // But since your Test model has NO sections, we create ONE section:

        $sections[] = [
            'roman'      => 'I',
            'title'      => $test->title,
            'direction'  => $test->testSettings->direction ?? '',
            'questions'  => $test->testQuestions, // <-- REAL DATA
        ];

        return $sections;
    }
}
