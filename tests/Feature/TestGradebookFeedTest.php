<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\ComponentScore;
use App\Models\GradeComponent;
use App\Models\GradingSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\Test;
use App\Models\User;
use App\Services\Grading\TestComponentFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A scanned answer sheet becomes a grade. A test tagged to a grade component rolls
 * its OMR results into component_scores (Σ raw ÷ Σ max × 100) — the same table the
 * gradebook reads — so the teacher never re-keys a scanned score.
 */
class TestGradebookFeedTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private ClassModel $class;

    private Student $student;

    private int $componentId;

    private int $subjectId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $termId = DB::table('terms')->insertGetId(['school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x', 'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31']);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $termId, 'name' => 'BSIT', 'year_level' => 1]);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'IT101', 'name' => 'IT']);

        // Higher-ed track: one component at 100% so the fed score is the whole grade.
        $levelId = DB::table('academic_levels')->insertGetId(['school_id' => $this->school->id, 'name' => 'Year 1', 'sequence_order' => 1, 'type' => 'higher']);
        $setting = GradingSetting::create(['school_id' => $this->school->id, 'academic_level_id' => $levelId, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0]);
        $this->componentId = GradeComponent::create(['school_id' => $this->school->id, 'grading_setting_id' => $setting->id, 'name' => 'Quiz', 'weight' => 100, 'sort_order' => 0])->id;

        $classId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $termId, 'teacher_id' => $teacher->id, 'section_id' => $sectionId, 'code' => 'IT101-A']);
        $this->class = ClassModel::findOrFail($classId);

        $this->student = Student::create(['school_id' => $this->school->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $enrollmentId = DB::table('student_enrollments')->insertGetId(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled']);
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $classId, 'subject_id' => $this->subjectId, 'status' => 'enrolled']);
    }

    private function makeTest(?int $componentId): Test
    {
        return Test::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subjectId,
            'title' => 'Exam',
            'status' => 'draft',
            'grade_component_id' => $componentId,
        ]);
    }

    /** A scanned sheet + its graded result for this student. */
    private function scanResult(Test $test, int $raw, int $max, bool $override = false): void
    {
        $sheetId = DB::table('omr_sheets')->insertGetId([
            'school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $this->student->id,
            'layout_version' => 'v2', 'token' => 'tok-'.uniqid(),
            'answer_key' => '[]', 'written_key' => '[]',
            'item_count' => $max, 'written_count' => 0, 'max_score' => $max, 'generated_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $attemptId = DB::table('omr_scan_attempts')->insertGetId([
            'school_id' => $this->school->id, 'omr_sheet_id' => $sheetId, 'marked_answers' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('omr_results')->insert([
            'school_id' => $this->school->id, 'omr_sheet_id' => $sheetId, 'scan_attempt_id' => $attemptId,
            'raw_score' => $raw, 'max_score' => $max,
            'percentage' => $max > 0 ? round($raw / $max * 100, 2) : 0,
            'correct_count' => $raw, 'incorrect_count' => $max - $raw, 'blank_count' => 0, 'multiple_count' => 0,
            'is_override' => $override, 'graded_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function score(): ?ComponentScore
    {
        return ComponentScore::where('student_id', $this->student->id)
            ->where('grade_component_id', $this->componentId)
            ->first();
    }

    public function test_a_scanned_test_writes_the_component_score(): void
    {
        $test = $this->makeTest($this->componentId);
        $this->scanResult($test, 8, 10);

        app(TestComponentFeed::class)->sync($test);

        $this->assertNotNull($this->score(), 'the scan should have produced a gradebook entry');
        $this->assertSame('80.00', (string) $this->score()->score);
        $this->assertSame((int) $this->class->subject_id, (int) $this->score()->subject_id);
    }

    public function test_an_untagged_test_never_touches_the_gradebook(): void
    {
        $test = $this->makeTest(null);
        $this->scanResult($test, 9, 10);

        app(TestComponentFeed::class)->sync($test);

        $this->assertNull($this->score(), 'an untagged test must not write a score');
    }

    public function test_several_tests_in_one_component_aggregate(): void
    {
        $first = $this->makeTest($this->componentId);
        $this->scanResult($first, 8, 10);
        $second = $this->makeTest($this->componentId);
        $this->scanResult($second, 12, 30);

        app(TestComponentFeed::class)->sync($second);

        // (8 + 12) / (10 + 30) = 50%, not the average of 80% and 40%.
        $this->assertSame('50.00', (string) $this->score()->score);
    }

    public function test_a_student_with_no_scan_yet_gets_no_score(): void
    {
        $test = $this->makeTest($this->componentId);

        app(TestComponentFeed::class)->sync($test);

        $this->assertNull($this->score(), 'nothing scanned yet means no entry, not a zero');
    }

    public function test_a_personal_test_with_no_class_is_ignored(): void
    {
        $test = Test::create([
            'school_id' => $this->school->id, 'class_id' => null, 'subject_id' => $this->subjectId,
            'title' => 'Personal', 'status' => 'draft', 'grade_component_id' => $this->componentId,
        ]);
        $this->scanResult($test, 10, 10);

        app(TestComponentFeed::class)->sync($test);

        $this->assertNull($this->score(), 'a test tied to no class has no gradebook to feed');
    }

    public function test_a_rescan_replaces_the_earlier_score(): void
    {
        $test = $this->makeTest($this->componentId);
        $this->scanResult($test, 4, 10);
        app(TestComponentFeed::class)->sync($test);
        $this->assertSame('40.00', (string) $this->score()->score);

        // Teacher re-scans and the result is corrected in place.
        DB::table('omr_results')->update(['raw_score' => 9, 'is_override' => true]);
        app(TestComponentFeed::class)->sync($test);

        $this->assertSame('90.00', (string) $this->score()->score);
        $this->assertSame(1, ComponentScore::where('student_id', $this->student->id)->count(), 'must update in place, not duplicate');
    }
}
