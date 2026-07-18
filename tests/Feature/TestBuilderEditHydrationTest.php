<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Teacher → Test Builder edit mode. The edit page must embed the saved test as
 * window.EDIT_TEST (consumed by hydrateBuilder.js) with the drill-down position
 * recovered from the source rows — a competency-level source implies its lesson
 * and topic, neither of which is stored on the tests row itself.
 */
class TestBuilderEditHydrationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private int $testId;

    private int $topicId;

    private int $lessonId;

    private int $competencyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $subjectId = DB::table('subjects')->insertGetId([
            'school_id' => $this->school->id,
            'code' => 'PHYS1',
            'name' => 'Physics',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->topicId = DB::table('topics')->insertGetId([
            'school_id' => $this->school->id,
            'subject_id' => $subjectId,
            'name' => 'Forces',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->lessonId = DB::table('lessons')->insertGetId([
            'school_id' => $this->school->id,
            'subject_id' => $subjectId,
            'topic_id' => $this->topicId,
            'name' => 'Gravity',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->competencyId = DB::table('competencies')->insertGetId([
            'school_id' => $this->school->id,
            'subject_id' => $subjectId,
            'topic_id' => $this->topicId,
            'lesson_id' => $this->lessonId,
            'name' => 'Explain weight vs mass',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->testId = DB::table('tests')->insertGetId([
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $subjectId,
            'title' => 'Untitled Test',
            'status' => 'draft',
            'difficulty' => json_encode(['average']),
            'academic_levels' => json_encode(['1']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('test_sources')->insert([
            'test_id' => $this->testId,
            'source_type' => 'competency',
            'source_id' => $this->competencyId,
            'mcq_count' => 20,
            'identification_count' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('test_settings')->insert([
            'test_id' => $this->testId,
            'mode' => 'f2f',
            'assessment_type' => 'Long Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_edit_page_embeds_the_hydration_payload(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.tests.edit', $this->testId));

        $response->assertOk();
        $response->assertSee('window.EDIT_TEST', false);
        $response->assertSee('hydrateBuilder.js', false);

        // Drill-down recovered from the competency source.
        $response->assertSee('"topic_id":'.$this->topicId, false);
        $response->assertSee('"lesson_id":'.$this->lessonId, false);

        // Saved source counts and settings travel with the payload.
        $response->assertSee('"source_type":"competency"', false);
        $response->assertSee('"mcq":20', false);
        $response->assertSee('"id":15', false);
        $response->assertSee('"mode":"f2f"', false);
        $response->assertSee('"assessment_type":"Long Test"', false);
    }

    public function test_create_page_has_no_hydration_payload(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.tests.create'));

        $response->assertOk();
        $response->assertDontSee('window.EDIT_TEST', false);
    }

    public function test_edit_page_is_tenant_guarded(): void
    {
        $foreignTeacher = User::factory()->create([
            'school_id' => School::factory()->create()->id,
            'role' => 'teacher',
        ]);

        $this->actingAs($foreignTeacher)
            ->get(route('teacher.tests.edit', $this->testId))
            ->assertNotFound();
    }
}
