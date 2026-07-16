<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\GradeComponent;
use App\Models\GradingSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Grading\GradebookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3c backend — the gradebook writes the permanent academic record, so this
 * pins the whole path: draft scores → compute → post, for both tracks, plus the
 * two safety guards (an incomplete grade is not posted; no resolvable scheme
 * posts nothing).
 */
class GradebookTest extends TestCase
{
    use RefreshDatabase;

    private GradebookService $gradebook;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gradebook = app(GradebookService::class);
    }

    private function ay(int $schoolId): int
    {
        return DB::table('academic_years')->insertGetId(['school_id' => $schoolId, 'name' => '2026-2027']);
    }

    private function term(int $schoolId): int
    {
        return DB::table('terms')->insertGetId([
            'school_id' => $schoolId, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
    }

    private function level(int $schoolId, string $name, int $seq, string $type): int
    {
        return DB::table('academic_levels')->insertGetId([
            'school_id' => $schoolId, 'name' => $name, 'sequence_order' => $seq, 'type' => $type,
        ]);
    }

    /** A scheme with WW 30 / PT 40 / QA 20 (=90) and optional attendance weight. */
    private function scheme(int $schoolId, int $levelId, float $attendanceWeight = 0): GradingSetting
    {
        $setting = GradingSetting::create([
            'school_id' => $schoolId, 'academic_level_id' => $levelId,
            'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => $attendanceWeight,
        ]);
        foreach ([['WW', 30], ['PT', 40], ['QA', 20]] as $i => [$name, $w]) {
            GradeComponent::create([
                'school_id' => $schoolId, 'grading_setting_id' => $setting->id,
                'name' => $name, 'weight' => $w, 'sort_order' => $i,
            ]);
        }

        return $setting->load('components');
    }

    private function student(int $schoolId): Student
    {
        return Student::create([
            'school_id' => $schoolId, 'student_number' => 'S-'.uniqid(), 'first_name' => 'A', 'last_name' => 'B',
        ]);
    }

    /** Map WW/PT/QA component ids → the 3 scores. */
    private function scoresByName(GradingSetting $setting, array $wwPtQa): array
    {
        $ids = $setting->components->pluck('id', 'name');

        return [
            $ids['WW'] => $wwPtQa[0],
            $ids['PT'] => $wwPtQa[1],
            $ids['QA'] => $wwPtQa[2],
        ];
    }

    /* --------------------------------------------------------- Higher ed */

    private function higherEdClass(int $schoolId, int $termId): ClassModel
    {
        $levelId = $this->level($schoolId, 'Year 1', 1, 'higher');
        $this->scheme($schoolId, $levelId); // resolves via section.year_level = 1
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $schoolId, 'term_id' => $termId, 'name' => 'BSIT-1A', 'year_level' => 1]);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $schoolId, 'code' => 'IT101', 'name' => 'IT']);
        $teacherId = User::factory()->create(['school_id' => $schoolId, 'role' => 'teacher'])->id;
        $classId = DB::table('classes')->insertGetId([
            'school_id' => $schoolId, 'subject_id' => $subjectId, 'term_id' => $termId,
            'teacher_id' => $teacherId, 'section_id' => $sectionId, 'code' => 'IT101-A',
        ]);

        return ClassModel::findOrFail($classId);
    }

    public function test_higher_ed_post_writes_the_final_to_the_enrollment_subject(): void
    {
        $school = School::factory()->create();
        $termId = $this->term($school->id);
        $ayId = $this->ay($school->id);
        $class = $this->higherEdClass($school->id, $termId);
        $setting = GradingSetting::with('components')->where('school_id', $school->id)->firstOrFail();
        $student = $this->student($school->id);

        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId,
            'term_id' => $termId, 'section_id' => $class->section_id, 'status' => 'enrolled',
        ]);
        DB::table('student_enrollment_subjects')->insert([
            'student_enrollment_id' => $enrollmentId, 'class_id' => $class->id,
            'subject_id' => $class->subject_id, 'status' => 'enrolled',
        ]);

        // WW90/PT80/QA70 → (2700+3200+1400)/90 = 81.11
        $this->gradebook->saveClassScores($class, [$student->id => $this->scoresByName($setting, [90, 80, 70])]);
        $this->assertDatabaseCount('component_scores', 3);

        $results = $this->gradebook->postClass($class);
        $this->assertTrue($results[$student->id]->isComplete);

        $ses = DB::table('student_enrollment_subjects')->where('class_id', $class->id)->first();
        $this->assertEqualsWithDelta(81.11, (float) $ses->final_grade, 0.01);
        $this->assertSame('passed', $ses->status);
    }

    public function test_incomplete_scores_are_not_posted(): void
    {
        $school = School::factory()->create();
        $termId = $this->term($school->id);
        $ayId = $this->ay($school->id);
        $class = $this->higherEdClass($school->id, $termId);
        $setting = GradingSetting::with('components')->where('school_id', $school->id)->firstOrFail();
        $student = $this->student($school->id);

        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId,
            'term_id' => $termId, 'section_id' => $class->section_id, 'status' => 'enrolled',
        ]);
        DB::table('student_enrollment_subjects')->insert([
            'student_enrollment_id' => $enrollmentId, 'class_id' => $class->id,
            'subject_id' => $class->subject_id, 'status' => 'enrolled',
        ]);

        // Only WW scored → PT/QA missing → incomplete.
        $ids = $setting->components->pluck('id', 'name');
        $this->gradebook->saveClassScores($class, [$student->id => [$ids['WW'] => 90]]);

        $results = $this->gradebook->postClass($class);
        $this->assertFalse($results[$student->id]->isComplete);

        $ses = DB::table('student_enrollment_subjects')->where('class_id', $class->id)->first();
        $this->assertNull($ses->final_grade, 'A partial grade must not be posted.');
    }

    public function test_a_class_with_no_resolvable_scheme_posts_nothing(): void
    {
        $school = School::factory()->create();
        $termId = $this->term($school->id);
        // Section year_level 9 → no matching higher-ed academic_level/scheme.
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'X', 'year_level' => 9]);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'Z', 'name' => 'Z']);
        $teacherId = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher'])->id;
        $classId = DB::table('classes')->insertGetId([
            'school_id' => $school->id, 'subject_id' => $subjectId, 'term_id' => $termId,
            'teacher_id' => $teacherId, 'section_id' => $sectionId, 'code' => 'Z-A',
        ]);

        $results = $this->gradebook->postClass(ClassModel::findOrFail($classId));
        $this->assertSame([], $results);
    }

    /* ---------------------------------------------------------- Basic ed */

    public function test_basic_ed_post_writes_a_report_card_grade(): void
    {
        $school = School::factory()->create();
        $termId = $this->term($school->id);
        $ayId = $this->ay($school->id);
        $levelId = $this->level($school->id, 'Grade 5', 5, 'basic');
        $setting = $this->scheme($school->id, $levelId);
        $nodeId = DB::table('education_nodes')->insertGetId(['name' => 'Grade 5', 'node_type' => 'grade']);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'MATH', 'name' => 'Math']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'Rizal']);
        $student = $this->student($school->id);

        DB::table('student_enrollments')->insert([
            'school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId,
            'term_id' => $termId, 'section_id' => $sectionId, 'education_node_id' => $nodeId, 'status' => 'enrolled',
        ]);

        $this->gradebook->saveNodeScores($school->id, $nodeId, $subjectId, 1, [$student->id => $this->scoresByName($setting, [90, 80, 70])]);
        $results = $this->gradebook->postNode($school->id, $nodeId, $subjectId, 1, $ayId);

        $this->assertTrue($results[$student->id]->isComplete);
        $this->assertDatabaseHas('report_card_grades', [
            'student_id' => $student->id, 'education_node_id' => $nodeId, 'subject_id' => $subjectId,
            'academic_year_id' => $ayId, 'grading_period' => 1,
        ]);
        $rcg = DB::table('report_card_grades')->where('student_id', $student->id)->first();
        $this->assertEqualsWithDelta(81.11, (float) $rcg->final_grade, 0.01);
    }

    public function test_basic_ed_folds_in_the_attendance_rate(): void
    {
        $school = School::factory()->create();
        $termId = $this->term($school->id);
        $ayId = $this->ay($school->id);
        $levelId = $this->level($school->id, 'Grade 5', 5, 'basic');
        $setting = $this->scheme($school->id, $levelId, attendanceWeight: 10); // 90 components + 10 attendance
        $nodeId = DB::table('education_nodes')->insertGetId(['name' => 'Grade 5', 'node_type' => 'grade']);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'MATH', 'name' => 'Math']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'Rizal']);
        DB::table('attendance_settings')->insert([
            'school_id' => $school->id, 'academic_level_id' => $levelId, 'capture_mode' => 'daily',
            'expected_days_per_period' => 4,
        ]);
        $student = $this->student($school->id);

        DB::table('student_enrollments')->insert([
            'school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId,
            'term_id' => $termId, 'section_id' => $sectionId, 'education_node_id' => $nodeId, 'status' => 'enrolled',
        ]);
        // 4 present daily records over 4 expected days → rate 100.
        foreach (['2026-06-01', '2026-06-02', '2026-06-03', '2026-06-04'] as $d) {
            DB::table('attendance_records')->insert([
                'school_id' => $school->id, 'student_id' => $student->id, 'scope' => 'daily',
                'section_id' => $sectionId, 'academic_year_id' => $ayId, 'attendance_date' => $d,
                'status' => 'present', 'method' => 'manual',
            ]);
        }

        $this->gradebook->saveNodeScores($school->id, $nodeId, $subjectId, 1, [$student->id => $this->scoresByName($setting, [90, 80, 70])]);
        $this->gradebook->postNode($school->id, $nodeId, $subjectId, 1, $ayId);

        // (7300 + 100*10) / 100 = 83.00
        $rcg = DB::table('report_card_grades')->where('student_id', $student->id)->first();
        $this->assertEqualsWithDelta(83.0, (float) $rcg->final_grade, 0.01);
    }
}
