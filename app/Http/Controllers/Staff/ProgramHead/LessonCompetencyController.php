<?php

namespace App\Http\Controllers\Staff\ProgramHead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Curriculum\CreateCurriculum;

class LessonCompetencyController extends Controller
{
    protected $curriculumService;

    public function __construct(CreateCurriculum $curriculumService)
    {
        $this->curriculumService = $curriculumService;
    }

    public function store(Request $request, $lessonId)
{
    // Find the parent lesson to get the hierarchy IDs
    $lesson = \App\Models\Lesson::findOrFail($lessonId);

    $request->validate([
        'competencies' => 'required|array',
        'competencies.*' => 'string|max:255'
    ]);

    // We pass all required IDs to the service
    $this->curriculumService->competencies(
        (int)$lesson->subject_id, 
        (int)$lesson->topic_id, 
        (int)$lesson->id, 
        $request->competencies
    );

    return response()->json(['success' => true, 'message' => 'Competencies saved.']);
}
}