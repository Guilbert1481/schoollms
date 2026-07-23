<?php

namespace Tests\Feature;

use App\Models\GradeComponent;
use App\Models\GradingSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Academics → Records: graded activities (online/paper tests + homework) in one
 * table with search/subject/period/date filters, the grading-scheme running
 * average (attendance excluded), and the Subjects-at-Risk modal.
 */
class StudentRecordsPageTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $studentUser;

    private Student $student;

    private int $enrollmentId;

    private int $termId;

    private int $sectionId;

    private int $subjectId;

    private int $classId;

    private int $componentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $this->student = Student::create(['school_id' => $this->school->id, 'user_id' => $this->studentUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);

        $this->termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'first', 'name' => 'First Semester', 'start_date' => '2026-06-01', 'end_date' => '2026-10-31',
            'education_level' => 'higher_ed',
        ]);
        $ayId = DB::table('academic_years')->where('school_id', $this->school->id)->where('is_active', 1)->value('id')
            ?? DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => 1]);

        $this->sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'BSIT 1A', 'year_level' => 1]);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'SCI', 'name' => 'Science']);
        $this->classId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $this->termId, 'teacher_id' => $this->teacher->id, 'section_id' => $this->sectionId, 'code' => 'SCI-A']);

        $levelId = DB::table('academic_levels')->insertGetId(['school_id' => $this->school->id, 'name' => 'Year 1', 'sequence_order' => 1, 'type' => 'higher']);
        $setting = GradingSetting::create(['school_id' => $this->school->id, 'academic_level_id' => $levelId, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0]);
        $this->componentId = GradeComponent::create(['school_id' => $this->school->id, 'grading_setting_id' => $setting->id, 'name' => 'Written Work', 'weight' => 60, 'sort_order' => 0])->id;

        $this->enrollmentId = DB::table('student_enrollments')->insertGetId([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'academic_year_id' => $ayId, 'term_id' => $this->termId,
            'status' => 'enrolled', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('student_enrollment_subjects')->insert([
            'student_enrollment_id' => $this->enrollmentId, 'class_id' => $this->classId,
            'subject_id' => $this->subjectId, 'status' => 'enrolled',
        ]);
    }

    private function makeTest(array $overrides = []): Test
    {
        return Test::create(array_merge([
            'school_id' => $this->school->id, 'class_id' => $this->classId, 'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id, 'title' => 'Unit Test', 'status' => 'published',
        ], $overrides));
    }

    private function gradedAttempt(Test $test, float $raw, float $max, float $pct, bool $needsManual = false): TestAttempt
    {
        $attempt = TestAttempt::create([
            'school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $this->student->id,
            'status' => 'in_progress', 'started_at' => now(),
        ]);
        DB::table('test_attempts')->where('id', $attempt->id)->update([
            'status' => 'graded', 'raw_score' => $raw, 'max_score' => $max, 'percentage' => $pct,
            'needs_manual' => $needsManual ? 1 : 0, 'submitted_at' => now(),
        ]);

        return $attempt->fresh();
    }

    public function test_records_page_renders_the_empty_state(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('student.records.index'))
            ->assertOk()
            ->assertSee('Records')
            ->assertSee('No graded activities yet');
    }

    public function test_online_test_row_shows_subject_type_competency_and_score(): void
    {
        $topicId = DB::table('topics')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'name' => 'Matter', 'created_at' => now(), 'updated_at' => now()]);
        $lessonId = DB::table('lessons')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'topic_id' => $topicId, 'name' => 'States of Matter', 'created_at' => now(), 'updated_at' => now()]);
        $competencyId = DB::table('competencies')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'topic_id' => $topicId, 'lesson_id' => $lessonId, 'name' => 'Classify matter by state', 'created_at' => now(), 'updated_at' => now()]);

        $test = $this->makeTest(['title' => 'Quiz 1', 'grade_component_id' => $this->componentId]);
        DB::table('test_settings')->insert(['test_id' => $test->id, 'assessment_type' => 'long_test', 'mode' => 'online', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('test_sources')->insert(['test_id' => $test->id, 'source_type' => 'competency', 'source_id' => $competencyId, 'created_at' => now(), 'updated_at' => now()]);

        $this->gradedAttempt($test, 8, 10, 80.0);

        $this->actingAs($this->studentUser)
            ->get(route('student.records.index'))
            ->assertOk()
            ->assertSee('Science')
            ->assertSee('Long Test')
            ->assertSee('Classify matter by state')
            ->assertSee('States of Matter')
            ->assertSee('8 / 10')
            ->assertSee('80.00%');
    }

    public function test_homework_row_shows_score_and_type(): void
    {
        $homeworkId = DB::table('homework')->insertGetId([
            'school_id' => $this->school->id, 'class_id' => $this->classId, 'title' => 'Essay draft',
            'points' => 5, 'due_at' => now()->addDays(3), 'is_published' => 1,
            'created_by' => $this->teacher->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('homework_submissions')->insert([
            'school_id' => $this->school->id, 'homework_id' => $homeworkId, 'student_id' => $this->student->id,
            'score' => 4, 'submitted_at' => now(), 'graded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->studentUser)
            ->get(route('student.records.index'))
            ->assertOk()
            ->assertSee('Homework')
            ->assertSee('Essay draft')
            ->assertSee('4 / 5')
            ->assertSee('80.00%');
    }

    public function test_subject_filter_narrows_the_rows(): void
    {
        $mathId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'MATH', 'name' => 'Mathematics']);
        $mathClassId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $mathId, 'term_id' => $this->termId, 'teacher_id' => $this->teacher->id, 'section_id' => $this->sectionId, 'code' => 'MATH-A']);

        $this->gradedAttempt($this->makeTest(['title' => 'Science Quiz']), 8, 10, 80.0);
        $this->gradedAttempt($this->makeTest(['title' => 'Math Quiz', 'subject_id' => $mathId, 'class_id' => $mathClassId]), 6, 10, 60.0);

        $this->actingAs($this->studentUser)
            ->get(route('student.records.index', ['subject_id' => $this->subjectId]))
            ->assertOk()
            ->assertSee('Science Quiz')
            ->assertDontSee('Math Quiz');
    }

    public function test_higher_ed_sees_semester_filter_and_basic_ed_sees_quarter(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('student.records.index'))
            ->assertOk()
            ->assertSee('All semesters')
            ->assertDontSee('All quarters');

        // A basic-ed student in a fresh school sees the quarter dropdown instead.
        $basicSchool = School::factory()->create();
        $basicUser = User::factory()->create(['school_id' => $basicSchool->id, 'role' => 'student']);
        $basicStudent = Student::create(['school_id' => $basicSchool->id, 'user_id' => $basicUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ben', 'last_name' => 'Reyes']);
        $basicTermId = DB::table('terms')->insertGetId([
            'school_id' => $basicSchool->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'first', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
            'education_level' => 'basic_ed',
        ]);
        $basicAy = DB::table('academic_years')->insertGetId(['school_id' => $basicSchool->id, 'name' => '2026-2027', 'is_active' => 1]);
        DB::table('student_enrollments')->insert([
            'school_id' => $basicSchool->id, 'student_id' => $basicStudent->id,
            'academic_year_id' => $basicAy, 'term_id' => $basicTermId,
            'status' => 'enrolled', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($basicUser)
            ->get(route('student.records.index'))
            ->assertOk()
            ->assertSee('All quarters')
            ->assertDontSee('All semesters');
    }

    public function test_running_average_weights_component_scores_and_excludes_attendance(): void
    {
        // Second component so the weighting is visible: 80×60 + 90×40 → 84.00.
        $setting = GradingSetting::where('school_id', $this->school->id)->first();
        $secondComponentId = GradeComponent::create(['school_id' => $this->school->id, 'grading_setting_id' => $setting->id, 'name' => 'Performance Tasks', 'weight' => 40, 'sort_order' => 1])->id;
        // A non-zero attendance weight must NOT change the Records average.
        $setting->update(['attendance_weight' => 20]);

        DB::table('component_scores')->insert([
            ['school_id' => $this->school->id, 'student_id' => $this->student->id, 'grade_component_id' => $this->componentId, 'class_id' => $this->classId, 'score' => 80, 'created_at' => now(), 'updated_at' => now()],
            ['school_id' => $this->school->id, 'student_id' => $this->student->id, 'grade_component_id' => $secondComponentId, 'class_id' => $this->classId, 'score' => 90, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($this->studentUser)
            ->get(route('student.records.index'))
            ->assertOk()
            ->assertSee('84.00');
    }

    public function test_at_risk_kpi_counts_and_modal_lists_reasons(): void
    {
        DB::table('student_enrollment_subjects')
            ->where('student_enrollment_id', $this->enrollmentId)
            ->update(['final_grade' => 70]);

        $this->actingAs($this->studentUser)
            ->get(route('student.records.index'))
            ->assertOk()
            ->assertSee('Subjects at Risk')
            ->assertSee('Average 70.00 — below passing (75)')
            ->assertSee('Raise your average by ~5 points to reach passing.');
    }
}
