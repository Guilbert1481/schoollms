<?php

namespace App\Services\Curriculum;

use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Competency;
use Illuminate\Support\Facades\Auth;

class DeleteCurriculum
{
    public function topic(int $topicId): void
    {
        Topic::where('school_id', Auth::user()->school_id)
            ->findOrFail($topicId)
            ->delete();
    }

    public function lesson(int $lessonId): void
    {
        Lesson::where('school_id', Auth::user()->school_id)
            ->findOrFail($lessonId)
            ->delete();
    }

    public function competency(int $competencyId): void
    {
        Competency::where('school_id', Auth::user()->school_id)
            ->findOrFail($competencyId)
            ->delete();
    }
}