<?php

namespace App\Http\Controllers\Staff\ProgramHead;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Subject;

class SubjectTopicController extends Controller
{

public function index()
{
    // withCount('topics') adds a virtual 'topics_count' attribute to each Subject
    $subjects = Subject::withCount('topics')->paginate(10);

    return view('program_head.subjects.index', compact('subjects'));
}

public function store(Request $request, $subjectId)
{
    $request->validate([
        'topics' => 'required|array',
        'topics.*' => 'string|max:255'
    ]);

    $subject = Subject::findOrFail($subjectId);

    foreach ($request->topics as $name) {
        $subject->topics()->firstOrCreate([
            'name' => $name,
            'school_id' => $subject->school_id, // Mandatory
        ]);
    }

    return response()->json(['success' => true]);
}
}