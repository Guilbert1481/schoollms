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
 * The manual "Add Record" gradebook path on the basic-ed track: records are keyed
 * by education node + learning area + grading period (never a class_id), and each
 * grading period is scored independently.
 */
class ManualGradeRecordBasicTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private int $nodeId;

    private int $subjectId;

    private User $teacher;

    private ClassModel $class;

    private Student $student;

    private int $wwId;

    protected function setUp(): void
    {
        parent::setUp();
        $school = School::factory()->create();
        $this->schoolId = $school->id;
        $this->teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);

        $termId = DB::table('terms')->insertGetId(['school_id' => $school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x', 'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31']);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $school->id, 'name' => '2026-2027']);

        $levelId = DB::table('academic_levels')->insertGetId(['school_id' => $school->id, 'name' => 'Grade 5', 'sequence_order' => 5, 'type' => 'basic']);
        $setting = GradingSetting::create(['school_id' => $school->id, 'academic_level_id' => $levelId, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0]);
        $this->wwId = GradeComponent::create(['school_id' => $school->id, 'grading_setting_id' => $setting->id, 'name' => 'WW', 'weight' => 100, 'sort_order' => 0])->id;

        $this->nodeId = DB::table('education_nodes')->insertGetId(['name' => 'Grade 5', 'node_type' => 'grade']);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'MATH', 'name' => 'Math']);

        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'Rizal']);
        $classId = DB::table('classes')->insertGetId(['school_id' => $school->id, 'subject_id' => $this->subjectId, 'term_id' => $termId, 'teacher_id' => $this->teacher->id, 'section_id' => $sectionId, 'code' => 'MATH-Rizal']);
        $this->class = ClassModel::findOrFail($classId);

        $this->student = Student::create(['school_id' => $school->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'A', 'last_name' => 'Rizal']);
        DB::table('student_enrollments')->insert(['school_id' => $school->id, 'student_id' => $this->student->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId, 'education_node_id' => $this->nodeId, 'status' => 'enrolled']);
    }

    private function addRecord(int $period, float $raw): void
    {
        $this->actingAs($this->teacher)->post(route('teacher.gradebook.record'), [
            'class_id' => $this->class->id,
            'grade_component_id' => $this->wwId,
            'total_items' => 30,
            'period' => $period,
            'scores' => [$this->student->id => $raw],
        ])->assertRedirect();
    }

    public function test_a_basic_ed_record_is_keyed_by_node_area_and_period(): void
    {
        $this->addRecord(1, 27);

        $this->assertDatabaseHas('grade_activities', [
            'grade_component_id' => $this->wwId, 'class_id' => null,
            'education_node_id' => $this->nodeId, 'subject_id' => $this->subjectId, 'grading_period' => 1,
        ]);
        $this->assertDatabaseHas('component_scores', [
            'student_id' => $this->student->id, 'grade_component_id' => $this->wwId, 'class_id' => null,
            'education_node_id' => $this->nodeId, 'subject_id' => $this->subjectId, 'grading_period' => 1,
        ]);

        $score = DB::table('component_scores')->where('student_id', $this->student->id)->where('grading_period', 1)->value('score');
        $this->assertSame('90.00', (string) $score); // 27 / 30 × 100
    }

    public function test_periods_are_scored_independently(): void
    {
        $this->addRecord(1, 27); // 90%
        $this->addRecord(2, 15); // 50%

        $p1 = DB::table('component_scores')->where('student_id', $this->student->id)->where('grading_period', 1)->value('score');
        $p2 = DB::table('component_scores')->where('student_id', $this->student->id)->where('grading_period', 2)->value('score');

        $this->assertSame('90.00', (string) $p1);
        $this->assertSame('50.00', (string) $p2);
    }
}
