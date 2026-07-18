<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Teacher → Tests → Test Management. The page must list ONLY the signed-in
 * teacher's own builder tests, and publish must be author-only — a teacher
 * flipping a colleague's (or another school's) draft live would change what
 * students can take, so both scopings are locked down here.
 */
class TestManagementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private User $teacher;

    private User $peerTeacher;

    private User $foreignTeacher;

    private int $ownTestId;

    private int $peerTestId;

    private int $foreignTestId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->otherSchool = School::factory()->create();

        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->peerTeacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->foreignTeacher = User::factory()->create(['school_id' => $this->otherSchool->id, 'role' => 'teacher']);

        $this->ownTestId = $this->makeTest($this->school->id, $this->teacher->id, 'Own Algebra Quiz');
        $this->peerTestId = $this->makeTest($this->school->id, $this->peerTeacher->id, 'Peer History Quiz');
        $this->foreignTestId = $this->makeTest($this->otherSchool->id, $this->foreignTeacher->id, 'Foreign Science Quiz');
    }

    private function makeTest(int $schoolId, int $teacherId, string $title, string $status = 'draft'): int
    {
        return DB::table('tests')->insertGetId([
            'school_id' => $schoolId,
            'teacher_id' => $teacherId,
            'title' => $title,
            'status' => $status,
            'academic_levels' => json_encode(['1']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_page_lists_only_the_signed_in_teachers_tests(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('teacher.tests.management'));

        $response->assertOk();
        $response->assertSee('Own Algebra Quiz');
        $response->assertDontSee('Peer History Quiz');
        $response->assertDontSee('Foreign Science Quiz');
    }

    public function test_teacher_can_publish_their_own_draft(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.tests.publish', $this->ownTestId));

        $response->assertRedirect(route('teacher.tests.management'));
        $this->assertSame('published', DB::table('tests')->where('id', $this->ownTestId)->value('status'));
    }

    public function test_teacher_cannot_publish_a_colleagues_test(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.tests.publish', $this->peerTestId));

        $response->assertForbidden();
        $this->assertSame('draft', DB::table('tests')->where('id', $this->peerTestId)->value('status'));
    }

    public function test_teacher_cannot_publish_another_schools_test(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.tests.publish', $this->foreignTestId));

        $response->assertNotFound();
        $this->assertSame('draft', DB::table('tests')->where('id', $this->foreignTestId)->value('status'));
    }

    public function test_students_cannot_reach_test_management(): void
    {
        $student = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);

        $response = $this->actingAs($student)->get(route('teacher.tests.management'));

        $response->assertStatus(403);
    }
}
