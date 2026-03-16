<?php

namespace App\Http\Controllers\Staff\ProgramHead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Curriculum\CreateCurriculum;

class TopicLessonController extends Controller
{
    protected $curriculumService;

    public function __construct(CreateCurriculum $curriculumService)
    {
        $this->curriculumService = $curriculumService;
    }

    public function store(Request $request, $topicId)
{
    // Find the topic to get its parent subject_id
    $topic = \App\Models\Topic::findOrFail($topicId);

    $request->validate([
        'lessons' => 'required|array',
        'lessons.*' => 'string|max:255'
    ]);

    // Pass: 1. Subject ID (from Topic), 2. Topic ID (from URL), 3. Lessons Array
    $this->curriculumService->lessons(
        (int)$topic->subject_id, 
        (int)$topic->id, 
        $request->lessons
    );

    return response()->json(['success' => true, 'message' => 'Lessons saved.']);
}


    public function index($subjectId)
    {
        $subject = \App\Models\Subject::with('topics.lessons')->findOrFail($subjectId);
        return view('program_head.subjects.lessons.index', compact('subject'));
    }
}