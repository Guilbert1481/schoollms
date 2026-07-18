<?php

namespace App\Http\Controllers\Scanner;

use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Home screen of the standalone Scanner app (the separate PWA installed from
 * /scan). Lists the signed-in teacher's tests that actually have printed OMR
 * sheets — those are the only ones there is anything to scan — so the app opens
 * straight into "pick a test → point the camera".
 *
 * Tenant scoping comes from Test's BelongsToSchool global scope; the list is
 * further narrowed to the teacher's own tests.
 */
class ScannerController extends Controller
{
    public function index()
    {
        $userId = (int) Auth::id();

        $tests = Test::query()
            ->where('teacher_id', $userId)
            ->whereIn('id', DB::table('omr_sheets')->select('test_id'))
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'title', 'subject_id', 'created_at']);

        // Sheet counts for the picker, in one query.
        $sheetCounts = DB::table('omr_sheets')
            ->whereIn('test_id', $tests->pluck('id'))
            ->groupBy('test_id')
            ->selectRaw('test_id, count(*) as sheets')
            ->pluck('sheets', 'test_id');

        // Subject names resolved directly rather than via with('subject'): that
        // relation is untyped legacy code, so static analysis cannot verify it.
        $subjectNames = DB::table('subjects')
            ->whereIn('id', $tests->pluck('subject_id')->filter()->unique())
            ->pluck('name', 'id');

        return view('scanner.index', compact('tests', 'sheetCounts', 'subjectNames'));
    }
}
