<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\GradeComponent;
use App\Models\GradingSetting;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Grading\GradebookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase B — graded homework feeds the gradebook automatically. A homework tagged
 * to a grade component, once graded, writes that component's score (Σ earned ÷
 * Σ possible × 100) into component_scores, which the gradebook reads — so the
 * computed grade includes homework with no double entry.
 */
class HomeworkGradebookFeedTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private ClassModel $class;

    private Student $student;

    private int $componentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $termId = DB::table('terms')->insertGetId(['school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x', 'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31']);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $termId, 'name' => 'BSIT', 'year_level' => 1]);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'IT101', 'name' => 'IT']);

        // Single-component scheme so the final equals the homework-fed component.
        $levelId = DB::table('academic_levels')->insertGetId(['school_id' => $this->school->id, 'name' => 'Year 1', 'sequence_order' => 1, 'type' => 'higher']);
        $setting = GradingSetting::create(['school_id' => $this->school->id, 'academic_level_id' => $levelId, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0]);
        $this->componentId = GradeComponent::create(['school_id' => $this->school->id, 'grading_setting_id' => $setting->id, 'name' => 'Homework', 'weight' => 100, 'sort_order' => 0])->id;

        $classId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $subjectId, 'term_id' => $termId, 'teacher_id' => $this->teacher->id, 'section_id' => $sectionId, 'code' => 'IT101-A']);
        $this->class = ClassModel::findOrFail($classId);

        $this->student = Student::create(['school_id' => $this->school->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $enrollmentId = DB::table('student_enrollments')->insertGetId(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled']);
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $classId, 'subject_id' => $subjectId, 'status' => 'enrolled']);
    }

    /** Post a tagged homework through the controller (exercises the component guard). */
    private function postHomework(float $points): Homework
    {
        $this->actingAs($this->teacher)->post(route('teacher.homework.store'), [
            'class_id' => $this->class->id, 'title' => 'HW', 'points' => $points,
            'is_published' => 1, 'grade_component_id' => $this->componentId,
        ])->assertRedirect();

        return Homework::withoutGlobalScopes()->where('class_id', $this->class->id)->orderByDesc('id')->first();
    }

    private function submission(Homework $hw): HomeworkSubmission
    {
        return HomeworkSubmission::withoutGlobalScopes()->create([
            'school_id' => $this->school->id, 'homework_id' => $hw->id, 'student_id' => $this->student->id, 'body' => 'x', 'submitted_at' => now(),
        ]);
    }

    private function grade(Homework $hw, HomeworkSubmission $sub, float $score): void
    {
        $this->actingAs($this->teacher)->post(route('teacher.homework.grade', $hw->id), [
            'grades' => [$sub->id => ['score' => $score]],
        ])->assertRedirect();
    }

    public function test_grading_tagged_homework_writes_the_component_score(): void
    {
        $hw = $this->postHomework(100);
        $this->assertSame($this->componentId, (int) $hw->grade_component_id);

        $this->grade($hw, $this->submission($hw), 90);

        $this->assertDatabaseHas('component_scores', [
            'student_id' => $this->student->id, 'grade_component_id' => $this->componentId, 'class_id' => $this->class->id,
        ]);
        $score = DB::table('component_scores')->where('student_id', $this->student->id)->where('grade_component_id', $this->componentId)->value('score');
        $this->assertEqualsWithDelta(90.0, (float) $score, 0.01);
    }

    public function test_multiple_homeworks_aggregate_by_points(): void
    {
        $a = $this->postHomework(100);
        $b = $this->postHomework(50);
        $this->grade($a, $this->submission($a), 90);
        $this->grade($b, $this->submission($b), 40);

        // (90 + 40) / (100 + 50) * 100 = 86.67
        $score = DB::table('component_scores')->where('student_id', $this->student->id)->where('grade_component_id', $this->componentId)->value('score');
        $this->assertEqualsWithDelta(86.67, (float) $score, 0.01);
    }

    public function test_the_fed_component_flows_into_the_gradebook_grade(): void
    {
        $hw = $this->postHomework(100);
        $this->grade($hw, $this->submission($hw), 88);

        // Single-component scheme → the computed final IS the homework-fed score.
        $result = app(GradebookService::class)->previewClass($this->class)[$this->student->id];
        $this->assertTrue($result->isComplete);
        $this->assertEqualsWithDelta(88.0, (float) $result->final, 0.01);
    }
}
