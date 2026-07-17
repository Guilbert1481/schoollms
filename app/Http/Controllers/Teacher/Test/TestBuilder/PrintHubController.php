<?php

namespace App\Http\Controllers\Teacher\Test\TestBuilder;

use App\Http\Controllers\Controller;
use App\Models\OmrResult;
use App\Models\OmrSheet;
use App\Models\Test;
use Illuminate\Http\JsonResponse;

/**
 * Print hub: one page with tabs for the questionnaire, answer sheet, and answer
 * key, plus a Reshuffle button. All three tabs render from the test's print_seed,
 * so a reshuffle keeps them in sync.
 */
class PrintHubController extends Controller
{
    public function show(Test $test)
    {
        abort_unless((int) $test->school_id === (int) auth()->user()->school_id, 404);

        return view('teacher.tests.test-builder.print-hub', ['test' => $test]);
    }

    /**
     * Reshuffle the print arrangement: pick a new seed and drop the frozen OMR
     * sheets so they regenerate from it. Refused once any sheet is graded, since
     * its result is tied to the current arrangement.
     */
    public function reshuffle(Test $test): JsonResponse
    {
        abort_unless((int) $test->school_id === (int) auth()->user()->school_id, 404);

        $sheetIds = OmrSheet::where('test_id', $test->id)->pluck('id');

        if ($sheetIds->isNotEmpty() && OmrResult::whereIn('omr_sheet_id', $sheetIds)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This test already has scanned results. Reshuffling would invalidate them.',
            ], 422);
        }

        $test->update(['print_seed' => random_int(1, 2_000_000_000)]);
        OmrSheet::whereIn('id', $sheetIds)->delete();

        return response()->json(['success' => true]);
    }
}
