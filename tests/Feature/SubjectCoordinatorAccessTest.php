<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    /**
     * Minimal offered education tree: a Basic Education root with two stage
     * groups, plus a separate higher-ed root. Enough for both tab styles to
     * render (level-tabs hides itself when only one entry exists).
     */
    private function seedEducationTree(): void
    {
        $basic = DB::table('education_nodes')->insertGetId([
            'name' => 'Basic Education', 'parent_id' => null, 'node_type' => 'level',
            'order_index' => 1, 'is_offered' => 1, 'is_active' => 1,
        ]);
        DB::table('education_nodes')->insert([
            ['name' => 'Elementary', 'parent_id' => $basic, 'node_type' => 'stage',
                'order_index' => 1, 'is_offered' => 1, 'is_active' => 1],
            ['name' => 'Junior High', 'parent_id' => $basic, 'node_type' => 'stage',
                'order_index' => 2, 'is_offered' => 1, 'is_active' => 1],
        ]);
        DB::table('education_nodes')->insert([
            'name' => 'Undergraduate Programs', 'parent_id' => null, 'node_type' => 'level',
            'order_index' => 2, 'is_offered' => 1, 'is_active' => 1,
        ]);
    }

    public function test_subject_coordinator_gets_basic_ed_stage_tabs_only(): void
    {
        $this->seedEducationTree();

        $this->actingAs($this->makeUser('subject_coordinator'))
            ->get(route('course-architect.lesson-studio.index'))
            ->assertOk()
            ->assertSee('Elementary')
            ->assertSee('Junior High')
            // Higher-ed roots must NOT be reachable for a basic-ed-only coordinator.
            ->assertDontSee('Undergraduate Programs');
    }

    public function test_course_architect_keeps_the_top_level_root_tabs(): void
    {
        $this->seedEducationTree();

        $this->actingAs($this->makeUser('course_architect'))
            ->get(route('course-architect.lesson-studio.index'))
            ->assertOk()
            // Roots variant: higher-ed stays visible; stage groups are not tabs here.
            ->assertSee('Undergraduate Programs')
            ->assertDontSee('Elementary');
    }
}
