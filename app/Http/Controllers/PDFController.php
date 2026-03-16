<?php

namespace App\Http\Controllers;

use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Models\Test;
use App\Traits\BuildsTestSections;


class PDFController extends Controller
{

    use BuildsTestSections;

    public function downloadTestPdf(Test $test)
{
    $test->load([
        'testQuestions.question.choices',
        'section.class.program.school',
        'testSettings',
    ]);

    $school = $test->section->class->program->school;



    $sections = $this->buildSections($test);

    $pdf = SnappyPdf::loadView('tests.test-builder.pdf', [
        'test' => $test,
        'sections' => $sections,
        'school' => $test->class->section->program->school,
    ]);

    $pdf->setPaper('a4');
    $pdf->setOption('margin-top', '15mm');
    $pdf->setOption('margin-bottom', '15mm');
    $pdf->setOption('margin-left', '12mm');
    $pdf->setOption('margin-right', '12mm');

    $pdf->setOption('footer-center', 'Page [page] of [topage]');
    $pdf->setOption('footer-font-size', '10');

    return $pdf->download($test->title . '.pdf');
}

}
