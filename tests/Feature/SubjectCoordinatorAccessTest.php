<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * subject_coordinator is the basic-ed counterpart of course_architect and
 * shares the entire Course Architect module (routes, sidebar, dashboard).
 * Regression for the 2026-07-20 role transfer: Nelson Mandela moved from
 * course_architect to subject_coordinator and must keep full access.
 */
class SubjectCoordinatorAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create([
            'school_id' => $this->school->id,
            'role' => $role,
        ]);
    }

    public function test_subject_coordinator_can_open_lesson_studio(): void
    {
        $this->actingAs($this->makeUser('subject_coordinator'))
            ->get(route('course-architect.lesson-studio.index'))
            ->assertOk();
    }

    public function test_subject_coordinator_gets_the_course_architect_dashboard(): void
    {
        $this->actingAs($this->makeUser('subject_coordinator'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('course-architect.dashboard');
    }

    public function test_course_architect_still_has_access(): void
    {
        $this->actingAs($this->makeUser('course_architect'))
            ->get(route('course-architect.lesson-studio.index'))
            ->assertOk();
    }

    public function test_student_is_still_denied(): void
    {
        $this->actingAs($this->makeUser('student'))
            ->get(route('course-architect.lesson-studio.index'))
            ->assertForbidden();
    }
}
