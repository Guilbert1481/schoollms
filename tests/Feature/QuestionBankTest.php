<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Teacher → Tests → Question Bank. The page must list ONLY the signed-in
 * teacher's own authored questions, the detail endpoint must respect the same
 * ownership, and delete must never touch a colleague's / another school's
 * question or one already placed on a test — those questions are part of what
 * students take, so every scoping is locked down here.
 */
class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private User $teacher;

    private User $peerTeacher;

    private User $foreignTeacher;

    private int $ownQuestionId;

    private int $peerQuestionId;

    private int $foreignQuestionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->otherSchool = School::factory()->create();

        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->peerTeacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->foreignTeacher = User::factory()->create(['school_id' => $this->otherSchool->id, 'role' => 'teacher']);

        $this->ownQuestionId = $this->makeQuestion($this->school->id, $this->teacher->id, 'Own photosynthesis item');
        $this->peerQuestionId = $this->makeQuestion($this->school->id, $this->peerTeacher->id, 'Peer mitosis item');
        $this->foreignQuestionId = $this->makeQuestion($this->otherSchool->id, $this->foreignTeacher->id, 'Foreign gravity item');
    }

    /** @var array<int, array{level:int,subject:int,topic:int}> */
    private array $curriculumBySchool = [];

    /** Insert a bank question with the curriculum rows its FKs require. */
    private function makeQuestion(int $schoolId, int $teacherId, string $text): int
    {
        ['level' => $levelId, 'subject' => $subjectId, 'topic' => $topicId] = $this->curriculumFor($schoolId);

        $questionId = DB::table('questions')->insertGetId([
            'school_id' => $schoolId,
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
            'topic_id' => $topicId,
            'academic_level_id' => $levelId,
            'question_type' => 'multiple_choice',
            'question_text' => $text,
            'difficulty' => 'easy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('choices')->insert([
            ['question_id' => $questionId, 'choice_text' => 'Right answer', 'is_correct' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['question_id' => $questionId, 'choice_text' => 'Wrong answer', 'is_correct' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        return $questionId;
    }

    /** One Grade 7 / Science / Living Things chain per school (unique keys). */
    private function curriculumFor(int $schoolId): array
    {
        return $this->curriculumBySchool[$schoolId] ??= (function () use ($schoolId) {
            $levelId = DB::table('academic_levels')->insertGetId([
                'school_id' => $schoolId,
                'name' => 'Grade 7',
                'sequence_order' => 7,
                'type' => 'basic',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $subjectId = DB::table('subjects')->insertGetId([
                'school_id' => $schoolId,
                'code' => 'SCI-'.$schoolId,
                'name' => 'Science',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $topicId = DB::table('topics')->insertGetId([
                'school_id' => $schoolId,
                'subject_id' => $subjectId,
                'name' => 'Living Things',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['level' => $levelId, 'subject' => $subjectId, 'topic' => $topicId];
        })();
    }

    public function test_page_lists_only_the_signed_in_teachers_questions(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('teacher.question_bank.index'));

        $response->assertOk();
        $response->assertSee('Own photosynthesis item');
        $response->assertDontSee('Peer mitosis item');
        $response->assertDontSee('Foreign gravity item');
    }

    public function test_students_cannot_reach_the_question_bank(): void
    {
        $student = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);

        $response = $this->actingAs($student)->get(route('teacher.question_bank.index'));

        $response->assertStatus(403);
    }

    public function test_detail_endpoint_returns_choices_for_an_own_question(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson(route('teacher.question_bank.show', $this->ownQuestionId));

        $response->assertOk();
        $response->assertJsonPath('question_text', 'Own photosynthesis item');
        $response->assertJsonPath('type_label', 'Multiple Choice');
        $response->assertJsonPath('choices.0.text', 'Right answer');
        $response->assertJsonPath('choices.0.correct', true);
        $response->assertJsonPath('choices.1.correct', false);
    }

    public function test_detail_endpoint_blocks_a_colleagues_question(): void
    {
        $this->actingAs($this->teacher)
            ->getJson(route('teacher.question_bank.show', $this->peerQuestionId))
            ->assertForbidden();
    }

    public function test_detail_endpoint_hides_another_schools_question(): void
    {
        $this->actingAs($this->teacher)
            ->getJson(route('teacher.question_bank.show', $this->foreignQuestionId))
            ->assertNotFound();
    }

    public function test_teacher_can_delete_their_own_unused_question(): void
    {
        $response = $this->actingAs($this->teacher)
            ->from(route('teacher.question_bank.index'))
            ->delete(route('teacher.question_bank.destroy', $this->ownQuestionId));

        $response->assertRedirect(route('teacher.question_bank.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('questions', ['id' => $this->ownQuestionId]);
        $this->assertDatabaseMissing('choices', ['question_id' => $this->ownQuestionId]);
    }

    public function test_delete_is_blocked_when_the_question_is_used_by_a_test(): void
    {
        $testId = DB::table('tests')->insertGetId([
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Quarter Exam',
            'status' => 'draft',
            'academic_levels' => json_encode(['1']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('test_questions')->insert([
            'test_id' => $testId,
            'question_id' => $this->ownQuestionId,
            'order' => 1,
            'points' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->teacher)
            ->from(route('teacher.question_bank.index'))
            ->delete(route('teacher.question_bank.destroy', $this->ownQuestionId));

        $response->assertRedirect(route('teacher.question_bank.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('questions', ['id' => $this->ownQuestionId]);
    }

    public function test_teacher_cannot_delete_a_colleagues_question(): void
    {
        $this->actingAs($this->teacher)
            ->delete(route('teacher.question_bank.destroy', $this->peerQuestionId))
            ->assertForbidden();

        $this->assertDatabaseHas('questions', ['id' => $this->peerQuestionId]);
    }

    public function test_teacher_cannot_delete_another_schools_question(): void
    {
        $this->actingAs($this->teacher)
            ->delete(route('teacher.question_bank.destroy', $this->foreignQuestionId))
            ->assertNotFound();

        $this->assertDatabaseHas('questions', ['id' => $this->foreignQuestionId]);
    }
}
