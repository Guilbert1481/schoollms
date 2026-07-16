<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Option 1 — the Assessment Levels picker gates the Subject dropdown. The
 * /teacher/tests/available-subjects endpoint returns only subjects that have
 * questions tagged at the selected academic_level(s), scoped to the teacher's
 * school. No level → no subjects.
 */
class AvailableSubjectsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private array $level = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'teacher',
        ]);

        foreach (['Grade 7' => 7, 'Grade 8' => 8] as $name => $seq) {
            $this->level[$name] = DB::table('academic_levels')->insertGetId([
                'school_id' => $this->school->id, 'name' => $name, 'sequence_order' => $seq, 'type' => 'basic',
            ]);
        }
    }

    private function subject(string $name, ?int $schoolId = null): int
    {
        return DB::table('subjects')->insertGetId([
            'school_id' => $schoolId ?? $this->school->id,
            'code' => $name.'-'.uniqid(),
            'name' => $name,
            'scope' => 'academic',
            'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function questionUnder(int $subjectId, int $levelId, ?int $schoolId = null): void
    {
        $topicId = DB::table('topics')->insertGetId([
            'school_id' => $schoolId ?? $this->school->id,
            'subject_id' => $subjectId,
            'name' => 'Topic',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('questions')->insert([
            'topic_id' => $topicId,
            'teacher_id' => $this->teacher->id,
            'academic_level_id' => $levelId,
            'question_type' => 'multiple_choice',
            'question_text' => 'Q',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function names(array $json): array
    {
        return array_column($json, 'name');
    }

    public function test_no_level_returns_no_subjects(): void
    {
        $this->subject('Math');

        $this->actingAs($this->teacher)
            ->getJson('/teacher/tests/available-subjects')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_only_subjects_with_questions_at_the_level_are_returned(): void
    {
        $math = $this->subject('Math');
        $science = $this->subject('Science');
        $this->subject('History'); // no questions → never appears

        $this->questionUnder($math, $this->level['Grade 7']);
        $this->questionUnder($science, $this->level['Grade 8']);

        $g7 = $this->actingAs($this->teacher)
            ->getJson('/teacher/tests/available-subjects?academic_levels[]='.$this->level['Grade 7'])
            ->assertOk()->json();

        $this->assertSame(['Math'], $this->names($g7));
    }

    public function test_multiple_levels_return_the_union(): void
    {
        $math = $this->subject('Math');
        $science = $this->subject('Science');
        $this->questionUnder($math, $this->level['Grade 7']);
        $this->questionUnder($science, $this->level['Grade 8']);

        $both = $this->actingAs($this->teacher)
            ->getJson('/teacher/tests/available-subjects?academic_levels[]='.$this->level['Grade 7'].'&academic_levels[]='.$this->level['Grade 8'])
            ->assertOk()->json();

        $this->assertEqualsCanonicalizing(['Math', 'Science'], $this->names($both));
    }

    public function test_subjects_from_another_school_are_excluded(): void
    {
        $other = School::factory()->create();
        $otherSubject = $this->subject('Foreign Math', $other->id);
        // A question at the same level id but under the other school's subject.
        $this->questionUnder($otherSubject, $this->level['Grade 7'], $other->id);

        $this->actingAs($this->teacher)
            ->getJson('/teacher/tests/available-subjects?academic_levels[]='.$this->level['Grade 7'])
            ->assertOk()
            ->assertExactJson([]);
    }
}
