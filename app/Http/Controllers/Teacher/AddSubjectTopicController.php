<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddSubjectTopicController extends Controller
{
    /**
     * Store a new Subject (normalized, no duplicates)
     */
    public function storeSubject(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        // Normalize
        $name = trim($request->name);
        $name = ucwords(strtolower($name));

        // Safe create (prevents duplicates)
        Subject::firstOrCreate([
            'name' => $name
        ]);

        return back()->with('success', 'Subject saved successfully.');
    }

    /**
     * Store a new Topic (unique per subject, normalized)
     */
    public function storeTopic(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name'       => 'required|string|max:255'
        ]);

        // Normalize
        $name = trim($request->name);
        $name = ucwords(strtolower($name));

        // Safe create (prevents duplicates per subject)
        Topic::firstOrCreate([
            'subject_id' => $request->subject_id,
            'name'       => $name
        ]);

        return back()->with('success', 'Topic saved successfully.');
    }
}
