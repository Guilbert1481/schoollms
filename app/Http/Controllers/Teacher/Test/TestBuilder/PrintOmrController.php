<?php

namespace App\Http\Controllers\Teacher\Test\TestBuilder;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Test;
use App\Services\Tests\OmrSheetService;
use App\Support\OmrLayout;
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

        $test->load(['subject', 'teacher', 'class']);

        $sectionId = $request->integer('section_id');
        if (! $sectionId) {
            $sections = $this->sheets->sectionsForPicker($test);

            // A class-bound test resolves to a single section — skip the picker
            // and go straight to its sheets. Personal/multi-section tests still
            // get the picker to choose from.
            if ($sections->count() === 1) {
                $sectionId = (int) $sections->first()->id;
            } else {
                return view('teacher.tests.test-builder.omr-select', [
                    'test' => $test,
                    'sections' => $sections,
                ]);
            }
        }

        $section = Section::findOrFail($sectionId);
        // Tenant guard on the section too.
        abort_unless((int) $section->school_id === (int) auth()->user()->school_id, 404);

        return view('teacher.tests.test-builder.omr', [
            'test' => $test,
            'section' => $section,
            // Same coverage line the questionnaire and answer key print, so all
            // three sheets describe the same scope.
            'coverage' => \App\Support\TestPrintHeader::for($test)['coverage'],
        ] + $this->sheets->build($test, $section));
    }

    /**
     * Manual record page (Phase 2a): pick a section, then key/confirm each
     * student's marks and submit to the scan endpoint for grading. Lets the whole
     * identify → grade → record flow be exercised before camera detection.
     */
    public function record(Test $test, Request $request)
    {
        abort_unless((int) $test->school_id === (int) auth()->user()->school_id, 404);

        $test->load(['subject', 'teacher']);

        $sections = $this->sheets->sectionsForPicker($test);
        $sectionId = $request->integer('section_id');

        $section = null;
        $roster = [];
        if ($sectionId) {
            $section = Section::findOrFail($sectionId);
            abort_unless((int) $section->school_id === (int) auth()->user()->school_id, 404);
            $roster = $this->sheets->recordRoster($test, $section);
        }

        return view('teacher.tests.test-builder.omr-record', [
            'test' => $test,
            'sections' => $sections,
            'section' => $section,
            'sectionId' => $sectionId,
            'roster' => $roster,
        ]);
    }

    /**
     * Camera scan page (Phase 2b): the device camera reads the QR + fiducials and
     * detects bubbles entirely in the browser (the image never leaves the device);
     * high-confidence reads auto-record, low-confidence ones drop to review. All
     * sheets in a test share one bubble layout, embedded here for the detector.
     */
    public function scanCamera(Test $test, Request $request)
    {
        abort_unless((int) $test->school_id === (int) auth()->user()->school_id, 404);

        $test->load(['subject', 'teacher', 'class']);

        $sections = $this->sheets->sectionsForPicker($test);
        $sectionId = $request->integer('section_id');

        $section = null;
        $roster = [];
        $itemCount = 0;
        $grid = [];
        $written = [];
        if ($sectionId) {
            $section = Section::findOrFail($sectionId);
            abort_unless((int) $section->school_id === (int) auth()->user()->school_id, 404);
            $roster = $this->sheets->recordRoster($test, $section);

            // Same sequenced geometry the printed sheet uses, so the detector
            // samples each bubble/box exactly where it was printed.
            $items = $this->sheets->layoutItems($test);
            $layout = OmrLayout::regions($items);
            $grid = $layout['bubbles'];
            $written = $layout['writes'];
            $itemCount = count(array_filter($items, fn ($it) => $it['kind'] === 'bubble'));
        }

        return view('teacher.tests.test-builder.omr-scan', [
            'test' => $test,
            'sections' => $sections,
            'section' => $section,
            'sectionId' => $sectionId,
            'roster' => $roster,
            'itemCount' => $itemCount,
            'grid' => $grid,
            'fiducials' => OmrLayout::fiducials(),
            'written' => $written,
        ]);
    }
}
