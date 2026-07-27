<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\GradeComponent;
use App\Models\GradingSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The manual "Add Record" gradebook path (higher-ed track). A teacher records a
 * hand-entered item (type + total items + each student's raw score); it stores a
 * grade_activity + per-student scores and recomputes the component into
 * component_scores as Σ raw ÷ Σ total × 100 — combining with online sources, not
 * clobbering them. Grade writes are guarded to this class's scheme and roster.
 */
class ManualGradeRecordTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private ClassModel $class;

    private Student $student;

    private int $componentId;

    private int $subjectId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $termId = DB::table('terms')->insertGetId(['school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x', 'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31']);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $termId, 'name' => 'BSIT', 'year_level' => 1]);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'IT101', 'name' => 'IT']);

        // Higher-ed track: one component at 100% so the fed score is the whole grade.
        $levelId = DB::table('academic_levels')->insertGetId(['school_id' => $this->school->id, 'name' => 'Year 1', 'sequence_order' => 1, 'type' => 'higher']);
        $setting = GradingSetting::create(['school_id' => $this->school->id, 'academic_level_id' => $levelId, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0]);
        $this->componentId = GradeComponent::create(['school_id' => $this->school->id, 'grading_setting_id' => $setting->id, 'name' => 'Quiz', 'weight' => 100, 'sort_order' => 0])->id;

        $classId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $termId, 'teacher_id' => $this->teacher->id, 'section_id' => $sectionId, 'code' => 'IT101-A']);
        $this->class = ClassModel::findOrFail($classId);

        $this->student = Student::create(['school_id' => $this->school->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $enrollmentId = DB::table('student_enrollments')->insertGetId(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled']);
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $classId, 'subject_id' => $this->subjectId, 'status' => 'enrolled']);
    }

    private function addRecord(array $payload)
    {
        return $this->actingAs($this->teacher)->post(route('teacher.gradebook.record'), array_merge([
            'class_id' => $this->class->id,
            'grade_component_id' => $this->componentId,
            'total_items' => 10,
        ], $payload));
    }

    private function score(): ?string
    {
        $v = DB::table('component_scores')
            ->where('student_id', $this->student->id)->where('grade_component_id', $this->componentId)
            ->value('score');

        return $v === null ? null : (string) $v;
    }

    public function test_add_record_stores_the_activity_scores_and_component_score(): void
    {
        $this->addRecord(['title' => 'Quiz 1', 'scores' => [$this->student->id => 8]])->assertRedirect();

        $this->assertDatabaseHas('grade_activities', [
            'grade_component_id' => $this->componentId, 'class_id' => $this->class->id,
            'total_items' => 10, 'title' => 'Quiz 1', 'education_node_id' => null,
        ]);
        $activityId = DB::table('grade_activities')->where('class_id', $this->class->id)->value('id');
        $this->assertDatabaseHas('grade_activity_scores', [
            'grade_activity_id' => $activityId, 'student_id' => $this->student->id, 'raw_score' => 8,
        ]);

        // 8 / 10 × 100 = 80.
        $this->assertSame('80.00', $this->score());
    }

    public function test_manual_activities_aggregate_by_points(): void
    {
        $this->addRecord(['total_items' => 10, 'scores' => [$this->student->id => 8]])->assertRedirect();
        $this->addRecord(['total_items' => 30, 'scores' => [$this->student->id => 12]])->assertRedirect();

        // (8 + 12) / (10 + 30) × 100 = 50 — not the average of 80% and 40%.
        $this->assertSame('50.00', $this->score());
    }

    public function test_a_blank_score_is_not_counted_as_zero(): void
    {
        $this->addRecord(['total_items' => 10, 'scores' => [$this->student->id => 9]])->assertRedirect();
        $this->addRecord(['total_items' => 10, 'scores' => [$this->student->id => '']])->assertRedirect();

        // The blank second item is excluded, so the score stays 9/10, not 9/20.
        $this->assertSame('90.00', $this->score());
    }

    public function test_manual_and_homework_combine_in_one_component(): void
    {
        // A graded homework already feeds the Quiz component: 90 / 100.
        $hwId = DB::table('homework')->insertGetId([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'title' => 'HW',
            'instructions' => 'x', 'points' => 100, 'due_at' => now(), 'grading_period' => null,
            'grade_component_id' => $this->componentId, 'is_published' => 1, 'created_by' => $this->teacher->id,
        ]);
        DB::table('homework_submissions')->insert([
            'school_id' => $this->school->id, 'homework_id' => $hwId, 'student_id' => $this->student->id,
            'body' => 'x', 'submitted_at' => now(), 'score' => 90,
        ]);

        // Then a manual quiz: 8 / 10. Recompute must SUM both sources, not overwrite.
        $this->addRecord(['total_items' => 10, 'scores' => [$this->student->id => 8]])->assertRedirect();

        // (8 + 90) / (10 + 100) × 100 = 89.09.
        $this->assertSame('89.09', $this->score());
    }

    public function test_an_off_scheme_component_is_rejected(): void
    {
        // A component that belongs to another school's scheme — not this class's.
        $other = School::factory()->create();
        $otherLevel = DB::table('academic_levels')->insertGetId(['school_id' => $other->id, 'name' => 'Y1', 'sequence_order' => 1, 'type' => 'higher']);
        $otherSetting = GradingSetting::create(['school_id' => $other->id, 'academic_level_id' => $otherLevel, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0]);
        $foreign = GradeComponent::create(['school_id' => $other->id, 'grading_setting_id' => $otherSetting->id, 'name' => 'X', 'weight' => 100, 'sort_order' => 0])->id;

        $this->addRecord(['grade_component_id' => $foreign, 'scores' => [$this->student->id => 8]])->assertRedirect();

        $this->assertDatabaseCount('grade_activities', 0);
        $this->assertNull($this->score());
    }

    public function test_only_roster_students_are_scored(): void
    {
        // A student not enrolled in this class.
        $outsider = Student::create(['school_id' => $this->school->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Bo', 'last_name' => 'Reyes']);

        $this->addRecord(['scores' => [$this->student->id => 8, $outsider->id => 10]])->assertRedirect();

        $this->assertDatabaseMissing('grade_activity_scores', ['student_id' => $outsider->id]);
        $this->assertDatabaseMissing('component_scores', ['student_id' => $outsider->id, 'grade_component_id' => $this->componentId]);
    }

    public function test_a_raw_score_above_the_item_count_is_rejected(): void
    {
        $this->addRecord(['total_items' => 10, 'scores' => [$this->student->id => 11]])
            ->assertSessionHasErrors('scores');

        $this->assertDatabaseCount('grade_activities', 0);
    }

    public function test_the_ledger_drawer_lists_the_recorded_item(): void
    {
        $this->addRecord(['title' => 'Quiz 1', 'scores' => [$this->student->id => 8]])->assertRedirect();

        $this->actingAs($this->teacher)
            ->get(route('teacher.gradebook.ledger', ['class_id' => $this->class->id, 'student_id' => $this->student->id]))
            ->assertOk()
            ->assertSee('Quiz 1')
            ->assertSee('8')
            ->assertSee('Component %');
    }
}
