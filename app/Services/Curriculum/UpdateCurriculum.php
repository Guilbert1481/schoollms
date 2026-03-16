<?php

namespace App\Services\Curriculum;

use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Competency;
use Illuminate\Support\Facades\Auth;

class UpdateCurriculum
{
    public function topic(int $topicId, string $newName): void
    {
        $topic = Topic::where('school_id', Auth::user()->school_id)
            ->findOrFail($topicId);

        $exists = Topic::where('subject_id', $topic->subject_id)
            ->where('name', $newName)
            ->where('id', '!=', $topic->id)
            ->exists();

        if (!$exists) {
            $topic->update(['name' => $newName]);
        }
    }

    public function lesson(int $lessonId, string $newName): void
    {
        $lesson = Lesson::where('school_id', Auth::user()->school_id)
            ->findOrFail($lessonId);

        $exists = Lesson::where('topic_id', $lesson->topic_id)
            ->where('name', $newName)
            ->where('id', '!=', $lesson->id)
            ->exists();

        if (!$exists) {
            $lesson->update(['name' => $newName]);
        }
    }

    public function competency(int $competencyId, string $newName): void
    {
        $competency = Competency::where('school_id', Auth::user()->school_id)
            ->findOrFail($competencyId);

        $exists = Competency::where('lesson_id', $competency->lesson_id)
            ->where('name', $newName)
            ->where('id', '!=', $competency->id)
            ->exists();

        if (!$exists) {
            $competency->update(['name' => $newName]);
        }
    }
}