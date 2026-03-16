<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Test;

class TestManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Test::with(['program', 'section']);

        // DIRECT COLUMNS
        foreach (['type', 'term', 'mode', 'status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'like', '%' . $request->$field . '%');
            }
        }

        // RELATION FILTERS
        if ($request->filled('subject')) {
            $query->whereHas('subject', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->subject . '%');
            });
        }

        if ($request->filled('program')) {
            $query->whereHas('program', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->program . '%');
            });
        }

        if ($request->filled('section')) {
            $query->whereHas('section', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->section . '%');
            });
        }

        $testManagement = $query
            ->paginate(10)
            ->withQueryString();

        return view('teacher.tests.testManagement', [
            'testManagement' => $testManagement
        ]);
    }

    public function publish(Test $test)
    {
        $test->update([
            'status' => 'published'
        ]);

        return redirect()
            ->route('teacher.tests.management')
            ->with('success', 'Test published successfully.');
    }

}
