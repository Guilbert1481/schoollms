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
 * Item #1 (option b) — basic-ed gradebook via a per-section teaching assignment.
 * A basic-ed class (subject + section + teacher, no student_enrollment_subjects)
 * grades per learning area × grading period into report_card_grades, and only
 * for the students in THAT class's section — so two sections of the same grade
 * are graded independently by their own teachers.
 */
class BasicGradebookTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private int $termId;

    private int $ayId;

    private int $nodeId;

    private int $subjectId;

    private array $componentIds;

    protected function setUp(): void
    {
        parent::setUp();
        $school = School::factory()->create();
        $this->schoolId = $school->id;

        $this->termId = DB::table('terms')->insertGetId([
            'school_id' => $school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
        $this->ayId = DB::table('academic_years')->insertGetId(['school_id' => $school->id, 'name' => '2026-2027']);

        // Basic-ed level "Grade 5" with a WW/PT/QA scheme, and the matching node.
        $levelId = DB::table('academic_levels')->insertGetId(['school_id' => $school->id, 'name' => 'Grade 5', 'sequence_order' => 5, 'type' => 'basic']);
        $setting = GradingSetting::create(['school_id' => $school->id, 'academic_level_id' => $levelId, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0]);
        foreach ([['WW', 30], ['PT', 40], ['QA', 20]] as $i => [$name, $w]) {
            GradeComponent::create(['school_id' => $school->id, 'grading_setting_id' => $setting->id, 'name' => $name, 'weight' => $w, 'sort_order' => $i]);
        }
        $this->componentIds = $setting->components()->pluck('id', 'name')->all();
        $this->nodeId = DB::table('education_nodes')->insertGetId(['name' => 'Grade 5', 'node_type' => 'grade']);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'MATH', 'name' => 'Math']);
    }

    /** A basic-ed class (section + Math + teacher) with one enrolled student in the section. */
    private function classWithStudent(User $teacher, string $sectionName): array
    {
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->schoolId, 'term_id' => $this->termId, 'name' => $sectionName]);
        $classId = DB::table('classes')->insertGetId([
            'school_id' => $this->schoolId, 'subject_id' => $this->subjectId, 'term_id' => $this->termId,
            'teacher_id' => $teacher->id, 'section_id' => $sectionId, 'code' => 'MATH-'.$sectionName,
        ]);
        $student = Student::create(['school_id' => $this->schoolId, 'student_number' => 'S-'.uniqid(), 'first_name' => 'A', 'last_name' => $sectionName]);
        DB::table('student_enrollments')->insert([
            'school_id' => $this->schoolId, 'student_id' => $student->id, 'academic_year_id' => $this->ayId,
            'term_id' => $this->termId, 'section_id' => $sectionId, 'education_node_id' => $this->nodeId, 'status' => 'enrolled',
        ]);

        return [ClassModel::findOrFail($classId), $student];
    }

    private function scores(int $classId, int $studentId, int $period): array
    {
        return [
            'class_id' => $classId, 'period' => $period,
            'scores' => [$studentId => [$this->componentIds['WW'] => 90, $this->componentIds['PT'] => 80, $this->componentIds['QA'] => 70]],
        ];
    }

    public function test_a_basic_ed_class_posts_a_report_card_grade(): void
    {
        $teacher = User::factory()->create(['school_id' => $this->schoolId, 'role' => 'teacher']);
        [$class, $student] = $this->classWithStudent($teacher, 'Rizal');

        // Draft: component_scores keyed by node + subject + period (not class).
        $this->actingAs($teacher)->post(route('teacher.gradebook.draft'), $this->scores($class->id, $student->id, 1))->assertRedirect();
        $this->assertDatabaseHas('component_scores', [
            'student_id' => $student->id, 'education_node_id' => $this->nodeId, 'subject_id' => $this->subjectId,
            'grading_period' => 1, 'class_id' => null,
        ]);

        // Post: report_card_grade for this student, node, subject, period.
        $this->actingAs($teacher)->post(route('teacher.gradebook.post'), $this->scores($class->id, $student->id, 1))->assertRedirect();
        $this->assertDatabaseHas('report_card_grades', [
            'student_id' => $student->id, 'education_node_id' => $this->nodeId, 'subject_id' => $this->subjectId,
            'academic_year_id' => $this->ayId, 'grading_period' => 1,
        ]);
        $rcg = DB::table('report_card_grades')->where('student_id', $student->id)->first();
        $this->assertEqualsWithDelta(81.11, (float) $rcg->final_grade, 0.01); // (2700+3200+1400)/90
    }

    public function test_a_teacher_only_grades_their_own_sections_students(): void
    {
        $teacherA = User::factory()->create(['school_id' => $this->schoolId, 'role' => 'teacher']);
        $teacherB = User::factory()->create(['school_id' => $this->schoolId, 'role' => 'teacher']);
        [$classA, $studentA] = $this->classWithStudent($teacherA, 'Rizal');
        [, $studentB] = $this->classWithStudent($teacherB, 'Bonifacio');

        // Teacher A posts for their class (section Rizal) only.
        $this->actingAs($teacherA)->post(route('teacher.gradebook.post'), $this->scores($classA->id, $studentA->id, 1))->assertRedirect();

        // Student A (Rizal) is graded; Student B (Bonifacio) is NOT — same grade level, different section.
        $this->assertDatabaseHas('report_card_grades', ['student_id' => $studentA->id, 'grading_period' => 1]);
        $this->assertDatabaseMissing('report_card_grades', ['student_id' => $studentB->id]);
    }

    public function test_periods_are_graded_independently(): void
    {
        $teacher = User::factory()->create(['school_id' => $this->schoolId, 'role' => 'teacher']);
        [$class, $student] = $this->classWithStudent($teacher, 'Rizal');

        $this->actingAs($teacher)->post(route('teacher.gradebook.post'), $this->scores($class->id, $student->id, 1))->assertRedirect();
        $this->actingAs($teacher)->post(route('teacher.gradebook.post'), $this->scores($class->id, $student->id, 2))->assertRedirect();

        $this->assertDatabaseHas('report_card_grades', ['student_id' => $student->id, 'grading_period' => 1]);
        $this->assertDatabaseHas('report_card_grades', ['student_id' => $student->id, 'grading_period' => 2]);
        $this->assertSame(2, DB::table('report_card_grades')->where('student_id', $student->id)->count());
    }
}
