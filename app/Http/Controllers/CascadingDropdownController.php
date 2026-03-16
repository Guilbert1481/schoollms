<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Competency;
use Illuminate\Http\JsonResponse;

class CascadingDropdownController extends Controller
{
    /**
     * =========================
     * SUBJECTS
     * =========================
     */
    public function subjects(): JsonResponse
    {
        return response()->json(
            Subject::select('id','code', 'name')
                ->orderBy('code')
                ->get()
        );
    }

    /**
     * =========================
     * SUBJECT → TOPICS
     * =========================
     */
    public function topics(Subject $subject): JsonResponse
    {
        return response()->json(
            Topic::where('subject_id', $subject->id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * =========================
     * TOPIC → LESSONS
     * =========================
     */
    /**
 * SUBJECT → TOPICS
 */
    public function lessons($topicId): JsonResponse
    {
        // The if check is good for safety
        if (!is_numeric($topicId)) {
            return response()->json([], 200);
        }

        // This fetches lessons where the topic_id matches the URL parameter
        return response()->json(
            Lesson::where('topic_id', $topicId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }


    /**
     * =========================
     * LESSON → COMPETENCIES
     * =========================
     */
    public function competencies(Lesson $lesson): JsonResponse
    {
        return response()->json(
            Competency::where('lesson_id', $lesson->id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }
}
