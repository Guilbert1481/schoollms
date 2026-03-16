<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Competency;
use Illuminate\Http\Request;

class TaxonomyController extends Controller
{
    public function subjects(Request $request)
    {
        $schoolId = $request->user()->school_id;
        return Subject::where('school_id', $schoolId)->select('id','name')->get();
    }

    public function topics(Request $request, $subjectId)
    {
        $schoolId = $request->user()->school_id;
        $subject = Subject::where('id', $subjectId)->where('school_id', $schoolId)->firstOrFail();
        return Topic::where('subject_id', $subject->id)->select('id','name')->get();
    }

    public function lessons(Request $request, $topicId)
    {
        $schoolId = $request->user()->school_id;
        $topic = Topic::where('id', $topicId)->where('school_id', $schoolId)->firstOrFail();
        return Lesson::where('topic_id', $topic->id)->select('id','name')->get();
    }

    public function competencies(Request $request, $lessonId)
    {
        $schoolId = $request->user()->school_id;
        $lesson = Lesson::where('id', $lessonId)->where('school_id', $schoolId)->firstOrFail();
        return Competency::where('lesson_id', $lesson->id)->select('id','name')->get();
    }
}