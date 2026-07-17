<?php

namespace App\Http\Controllers\Teacher\Test\TestBuilder;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Test;
use App\Services\Tests\OmrSheetService;
use Illuminate\Http\Request;

class PrintOmrController extends Controller
{
    public function __construct(private OmrSheetService $sheets) {}

    /**
     * Printable OMR answer sheets — one per enrolled student in the chosen
     * section. Without ?section_id, shows a picker of the school's sections that
     * have students (section is chosen at print time).
     */
    public function print(Test $test, Request $request)
    {
        // Tenant guard: never expose a test from another school.
        abort_unless((int) $test->school_id === (int) auth()->user()->school_id, 404);

        $test->load(['subject', 'teacher']);

        $sectionId = $request->integer('section_id');
        if (! $sectionId) {
            return view('teacher.tests.test-builder.omr-select', [
                'test' => $test,
                'sections' => $this->sheets->sectionsForPicker($test),
            ]);
        }

        $section = Section::findOrFail($sectionId);
        // Tenant guard on the section too.
        abort_unless((int) $section->school_id === (int) auth()->user()->school_id, 404);

        return view('teacher.tests.test-builder.omr', [
            'test' => $test,
            'section' => $section,
        ] + $this->sheets->build($test, $section));
    }
}
