<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\Request;
use App\Support\AcademicNormalizer;

class TopicController extends Controller
{
    /**
     * Store a new topic
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name'       => 'required|string|max:255',
        ]);

        Topic::create([
            'subject_id' => $data['subject_id'],
            'teacher_id' => auth()->id(), // 🔐 required
            'name'       => AcademicNormalizer::normalize($data['name']),
        ]);

        return back()->with('success', 'Topic added successfully.');
    }

    /**
     * Fetch topics by subject (AJAX dropdown)
     */
    public function bySubject($subjectId)
    {
        return response()->json(
            Topic::where('subject_id', $subjectId)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }
}
