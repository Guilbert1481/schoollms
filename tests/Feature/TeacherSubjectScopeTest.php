<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A teacher may only see — and author for — the subjects assigned to them via
 * the teacher_subjects pivot (keyed by teacher_profiles.id). Managers keep the
 * full school catalogue. The rule is enforced in the subject pickers AND on
 * save, because hiding a dropdown is not access control.
 */
class TeacherSubjectScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private int $profileId;

    private int $assignedMath;

    private int $assignedScience;

    private int $unassignedHistory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        $this->teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'teacher',
        ]);

        $this->profileId = DB::table('teacher_profiles')->insertGetId([
            'user_id' => $this->teacher->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assignedMath = $this->subject('Math');
        $this->assignedScience = $this->subject('Science');
        $this->unassignedHistory = $this->subject('History');

        // Assign only Math + Science to this teacher.
        foreach ([$this->assignedMath, $this->assignedScience] as $sid) {
            DB::table('teacher_subjects')->insert([
                'teacher_id' => $this->profileId,
                'subject_id' => $sid,
                'qualification_level' => 'primary',
                'created_at' => now(), 'updated_at' => now(),
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

    private function questionUnder(int $subjectId, int $levelId): void
    {
        $topicId = DB::table('topics')->insertGetId([
            'school_id' => $this->school->id,
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

    private function level(string $name = 'Grade 7', int $seq = 7): int
    {
        return DB::table('academic_levels')->insertGetId([
            'school_id' => $this->school->id, 'name' => $name, 'sequence_order' => $seq, 'type' => 'basic',
        ]);
    }

    /** The shared subject dropdown (/api/subjects) lists only assigned subjects for a teacher. */
    public function test_subject_dropdown_lists_only_assigned_subjects(): void
    {
        $names = array_column(
            $this->actingAs($this->teacher)->getJson('/api/subjects')->assertOk()->json(),
            'name'
        );

        $this->assertEqualsCanonicalizing(['Math', 'Science'], $names);
        $this->assertNotContains('History', $names);
    }

    /** A non-teacher (manager) still sees the whole school catalogue. */
    public function test_manager_sees_all_school_subjects(): void
    {
        $head = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'program_head',
        ]);

        $names = array_column(
            $this->actingAs($head)->getJson('/api/subjects')->assertOk()->json(),
            'name'
        );

        $this->assertEqualsCanonicalizing(['Math', 'Science', 'History'], $names);
    }

    /** A teacher with no assignments (no profile) sees nothing rather than everything. */
    public function test_teacher_without_profile_sees_no_subjects(): void
    {
        $orphan = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'teacher',
        ]);

        $this->actingAs($orphan)
            ->getJson('/api/subjects')
            ->assertOk()
            ->assertExactJson([]);
    }

    /** The level-gated Test Builder list also drops unassigned subjects, even those with questions. */
    public function test_available_subjects_excludes_unassigned_even_with_questions(): void
    {
        $level = $this->level();

        $this->questionUnder($this->assignedMath, $level);
        $this->questionUnder($this->unassignedHistory, $level);

        $names = array_column(
            $this->actingAs($this->teacher)
                ->getJson('/teacher/tests/available-subjects?academic_levels[]='.$level)
                ->assertOk()->json(),
            'name'
        );

        $this->assertSame(['Math'], $names);
    }

    /** Saving question metadata for an unassigned subject is rejected server-side. */
    public function test_save_metadata_rejects_unassigned_subject(): void
    {
        $level = $this->level();

        $this->actingAs($this->teacher)
            ->post('/teacher/tests/save-metadata', [
                'subject_id' => $this->unassignedHistory,
                'question_type' => 'mcq',
                'academic_level_id' => $level,
            ])
            ->assertSessionHasErrors('subject_id');

        // An assigned subject clears the subject check.
        $this->actingAs($this->teacher)
            ->post('/teacher/tests/save-metadata', [
                'subject_id' => $this->assignedMath,
                'question_type' => 'mcq',
                'academic_level_id' => $level,
            ])
            ->assertSessionHasNoErrors();
    }

    /** The Test Builder save endpoint forbids an unassigned subject (defense-in-depth). */
    public function test_save_builder_forbids_unassigned_subject(): void
    {
        $this->actingAs($this->teacher)
            ->postJson('/teacher/tests/builder/save', [
                'subject_id' => $this->unassignedHistory,
            ])
            ->assertForbidden();
    }
}
