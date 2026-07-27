<?php

namespace Tests\Feature;

use App\Models\GradeComponent;
use App\Models\GradingSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\Dashboard\StudentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Multi-source Subjects-at-Risk detection. A subject flags when its best
 * available measure is below passing: a posted grade (higher-ed enrolled
 * subjects OR basic-ed report-card quarters), else a running measure from
 * graded activity (grading-scheme components first, raw activity mean as the
 * fallback). Advisory display only — nothing writes a grade.
 */
class SubjectsAtRiskDetectionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $studentUser;

    private Student $student;

    private int $enrollmentId;

    private int $sectionId;

    private int $subjectId;

    private int $classId;

    private int $ayId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $this->student = Student::create(['school_id' => $this->school->id, 'user_id' => $this->studentUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Psalms', 'last_name' => 'Jabinar']);

        $termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'first', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
            'education_level' => 'basic_ed',
        ]);
        $this->ayId = DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => 1]);
        $this->sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $termId, 'name' => 'G6-A', 'year_level' => 6]);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'SCI', 'name' => 'Science']);
        $this->classId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $termId, 'teacher_id' => $this->teacher->id, 'section_id' => $this->sectionId, 'code' => 'SCI-6A']);

        $this->enrollmentId = DB::table('student_enrollments')->insertGetId([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'academic_year_id' => $this->ayId, 'term_id' => $termId, 'section_id' => $this->sectionId,
            'status' => 'enrolled', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** A graded online attempt at the given percentage on a fresh test of the class. */
    private function gradedAttempt(float $percentage): void
    {
        $test = Test::create(['school_id' => $this->school->id, 'class_id' => $this->classId, 'subject_id' => $this->subjectId, 'teacher_id' => $this->teacher->id, 'title' => 'Activity '.uniqid(), 'status' => 'published']);
        $attempt = TestAttempt::create(['school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $this->student->id, 'status' => 'in_progress', 'started_at' => now()]);
        DB::table('test_attempts')->where('id', $attempt->id)->update([
            'status' => 'graded', 'raw_score' => $percentage, 'max_score' => 100,
            'percentage' => $percentage, 'submitted_at' => now(),
        ]);
    }

    private function atRisk(): array
    {
        return app(StudentDashboardService::class)->subjectsAtRisk($this->studentUser->fresh());
    }

    public function test_failed_activities_flag_the_subject_via_running_average(): void
    {
        $this->gradedAttempt(20);
        $this->gradedAttempt(16);

        $rows = $this->atRisk();

        $this->assertCount(1, $rows);
        $this->assertSame('Science', $rows[0]['subject']);
        $this->assertSame('18.00', $rows[0]['average']);
        $this->assertSame(2, $rows[0]['failed_tests']);
        $reasons = implode(' ', $rows[0]['reasons']);
        $this->assertStringContainsString('Running average 18.00', $reasons);
        $this->assertStringContainsString('no grade posted yet', $reasons);
        $this->assertStringContainsString('2 failed quiz/long tests', $reasons);
        $this->assertNotSame('', $rows[0]['recommendation']);
    }

    public function test_kpi_count_and_records_page_agree_with_the_detection(): void
    {
        $this->gradedAttempt(20);

        $summary = app(StudentDashboardService::class)->summary($this->studentUser->fresh());
        $this->assertSame(1, $summary['subject_at_risk']['value']);

        $this->actingAs($this->studentUser)
            ->get(route('student.records.index'))
            ->assertOk()
            ->assertSee('Running average 20.00');
    }

    public function test_posted_report_card_grade_flags_a_basic_ed_subject_and_outranks_running(): void
    {
        $nodeId = DB::table('education_nodes')->insertGetId(['name' => 'Grade 6', 'node_type' => 'grade', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('report_card_grades')->insert([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'education_node_id' => $nodeId, 'subject_id' => $this->subjectId,
            'academic_year_id' => $this->ayId, 'grading_period' => 1, 'final_grade' => 70,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->gradedAttempt(95); // strong running work must NOT hide a posted failing grade

        $rows = $this->atRisk();

        $this->assertCount(1, $rows);
        $this->assertSame('70.00', $rows[0]['average']);
        $this->assertStringContainsString('Average 70.00 — below passing (75)', $rows[0]['reasons'][0]);
        $this->assertStringNotContainsString('Running', $rows[0]['reasons'][0]);
    }

    public function test_a_passing_running_average_is_not_flagged(): void
    {
        $this->gradedAttempt(80);

        $this->assertSame([], $this->atRisk());
    }

    public function test_component_scores_outweigh_the_raw_activity_mean(): void
    {
        // Grading scheme: Written Work 60 + Performance Tasks 40.
        $levelId = DB::table('academic_levels')->insertGetId(['school_id' => $this->school->id, 'name' => 'Grade 6', 'sequence_order' => 6, 'type' => 'basic']);
        $setting = GradingSetting::create(['school_id' => $this->school->id, 'academic_level_id' => $levelId, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0]);
        $ww = GradeComponent::create(['school_id' => $this->school->id, 'grading_setting_id' => $setting->id, 'name' => 'Written Work', 'weight' => 60, 'sort_order' => 0]);
        $pt = GradeComponent::create(['school_id' => $this->school->id, 'grading_setting_id' => $setting->id, 'name' => 'Performance Tasks', 'weight' => 40, 'sort_order' => 1]);

        // Official component standing is passing (80×60 + 90×40 → 84)…
        DB::table('component_scores')->insert([
            ['school_id' => $this->school->id, 'student_id' => $this->student->id, 'grade_component_id' => $ww->id, 'subject_id' => $this->subjectId, 'score' => 80, 'created_at' => now(), 'updated_at' => now()],
            ['school_id' => $this->school->id, 'student_id' => $this->student->id, 'grade_component_id' => $pt->id, 'subject_id' => $this->subjectId, 'score' => 90, 'created_at' => now(), 'updated_at' => now()],
        ]);
        // …even though one raw activity was a 20% fail.
        $this->gradedAttempt(20);

        $this->assertSame([], $this->atRisk());
    }

    public function test_higher_ed_posted_grade_still_flags(): void
    {
        DB::table('student_enrollment_subjects')->insert([
            'student_enrollment_id' => $this->enrollmentId, 'class_id' => $this->classId,
            'subject_id' => $this->subjectId, 'status' => 'enrolled', 'final_grade' => 70,
        ]);

        $rows = $this->atRisk();

        $this->assertCount(1, $rows);
        $this->assertSame('70.00', $rows[0]['average']);
        $this->assertStringContainsString('Average 70.00 — below passing (75)', $rows[0]['reasons'][0]);
    }
}
