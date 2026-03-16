<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Competency;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_test_info()
    {
        // factories should create relationships and school_id
        $school = \App\Models\School::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher', 'school_id' => $school->id]);

        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $topic = Topic::factory()->create(['school_id' => $school->id, 'subject_id' => $subject->id]);
        $lesson = Lesson::factory()->create(['school_id' => $school->id, 'topic_id' => $topic->id]);
        $competency = Competency::factory()->create(['school_id' => $school->id, 'lesson_id' => $lesson->id]);

        $payload = [
            'subject_id' => $subject->id,
            'topic_id' => $topic->id,
            'lesson_id' => $lesson->id,
            'competency_id' => $competency->id,
            'academic_level' => 'junior_high',
            'question_type' => 'mcq',
            'title' => 'Sample test'
        ];

        $this->actingAs($teacher)
             ->postJson(route('teacher.tests.info.store'), $payload)
             ->assertStatus(201)
             ->assertJsonStructure(['id']);
    }

    public function test_validation_fails_when_topic_missing()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $payload = [
            'subject_id' => 1,
            // topic_id missing
            'lesson_id' => 1,
            'competency_id' => 1,
            'academic_level' => 'junior_high',
            'question_type' => 'mcq',
        ];

        $this->actingAs($teacher)
             ->postJson(route('teacher.tests.info.store'), $payload)
             ->assertStatus(422);
    }
}