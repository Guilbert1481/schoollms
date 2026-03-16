<?php

namespace App\Services\Curriculum;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Competency;
use Illuminate\Support\Facades\Auth;

class CreateCurriculum
{

    /**
     * @param int $subjectId
     * @param int $topicId
     * @param array $lessons
     */

    public function topics(int $subjectId, array $topics): void
    {
        $subject = Subject::where('school_id', Auth::user()->school_id)
            ->findOrFail($subjectId);

        foreach ($topics as $name) {

            $exists = Topic::where('subject_id', $subject->id)
                ->where('name', $name)
                ->exists();

            if (!$exists) {
                Topic::create([
                    'subject_id' => $subject->id,
                    'school_id' => Auth::user()->school_id,
                    'name' => $name,
                ]);
            }
        }
    }

    public function lessons(int $subjectId, int $topicId, array $lessons)
    {
        foreach ($lessons as $name) {
            \App\Models\Lesson::create([
                'name' => $name,
                'topic_id' => $topicId,
                'subject_id' => $subjectId, // Fixes "Field subject_id doesn't have a default value"
                'school_id' => auth()->user()->school_id, // Multi-tenant support for Sophentis
            ]);
        }
    }

    public function competencies(int $subjectId, int $topicId, int $lessonId, array $competencies)
    {
        foreach ($competencies as $name) {
            \App\Models\Competency::create([
                'name' => $name,             // Use 'title' or 'name' based on your schema
                'lesson_id' => $lessonId,
                'topic_id' => $topicId,       // Added to fix potential SQL 1364 errors
                'subject_id' => $subjectId,   // Added to fix SQL 1364: Field 'subject_id' doesn't have a default value
                'school_id' => auth()->user()->school_id,
            ]);
        }
    }
}