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
 * Track resolution for the teacher gradebook. A basic-ed section (its roster
 * carries an education node) must grade on the BASIC track even when a stray
 * higher-ed student_enrollment_subjects row exists — otherwise the class is
 * misrouted to higher ed and the configured basic grading scheme is never found
 * (the "Grade 5 scheme set up but gradebook still says none" bug).
 */
class GradebookTrackResolutionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private int $termId;

    private int $ayId;

    private int $subjectId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $this->termId = DB::table('terms')->insertGetId(['school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x', 'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31']);
        $this->ayId = DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027']);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'MATH', 'name' => 'Mathematics']);
    }

    private function class(int $sectionId): ClassModel
    {
        $id = DB::table('classes')->insertGetId([
            'school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $this->termId,
            'teacher_id' => $this->teacher->id, 'section_id' => $sectionId, 'code' => 'MATH-'.$sectionId,
        ]);

        return ClassModel::findOrFail($id);
    }

    private function student(): Student
    {
        return Student::create(['school_id' => $this->school->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Winston', 'last_name' => 'Churchill']);
    }

    public function test_basic_section_resolves_basic_even_with_a_stray_ses_row(): void
    {
        // A Grade 5 basic-ed section: student enrolls with an education node.
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'Patient', 'year_level' => 5]);
        $nodeId = DB::table('education_nodes')->insertGetId(['name' => 'Grade 5', 'parent_id' => null, 'node_type' => 'stage', 'order_index' => 0, 'is_offered' => 1, 'is_active' => 1]);
        $class = $this->class($sectionId);
        $student = $this->student();

        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'school_id' => $this->school->id, 'student_id' => $student->id, 'academic_year_id' => $this->ayId,
            'term_id' => $this->termId, 'section_id' => $sectionId, 'education_node_id' => $nodeId, 'status' => 'enrolled',
        ]);

        // The stray higher-ed marker that used to misroute the class.
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $class->id, 'subject_id' => $this->subjectId, 'status' => 'enrolled']);

        $this->assertSame('basic', app(GradebookService::class)->classTrack($class));
    }

    public function test_higher_section_still_resolves_higher(): void
    {
        // No education node on the roster → genuine higher-ed class.
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'BSIT-1A', 'year_level' => 1]);
        $class = $this->class($sectionId);
        $student = $this->student();

        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'school_id' => $this->school->id, 'student_id' => $student->id, 'academic_year_id' => $this->ayId,
            'term_id' => $this->termId, 'section_id' => $sectionId, 'status' => 'enrolled',
        ]);
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $class->id, 'subject_id' => $this->subjectId, 'status' => 'enrolled']);

        $this->assertSame('higher', app(GradebookService::class)->classTrack($class));
    }

    public function test_gradebook_finds_the_basic_scheme_for_the_class(): void
    {
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'Patient', 'year_level' => 5]);
        $nodeId = DB::table('education_nodes')->insertGetId(['name' => 'Grade 5', 'parent_id' => null, 'node_type' => 'stage', 'order_index' => 0, 'is_offered' => 1, 'is_active' => 1]);
        $class = $this->class($sectionId);
        $student = $this->student();

        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'school_id' => $this->school->id, 'student_id' => $student->id, 'academic_year_id' => $this->ayId,
            'term_id' => $this->termId, 'section_id' => $sectionId, 'education_node_id' => $nodeId, 'status' => 'enrolled',
        ]);
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $class->id, 'subject_id' => $this->subjectId, 'status' => 'enrolled']);

        // The Principal's Grade 5 basic scheme (forNode matches academic_level by name).
        $levelId = DB::table('academic_levels')->insertGetId(['school_id' => $this->school->id, 'name' => 'Grade 5', 'sequence_order' => 5, 'type' => 'basic']);
        $setting = GradingSetting::create(['school_id' => $this->school->id, 'academic_level_id' => $levelId, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 10]);
        GradeComponent::create(['school_id' => $this->school->id, 'grading_setting_id' => $setting->id, 'name' => 'Written Work', 'weight' => 90, 'sort_order' => 0]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.gradebook.index', ['class_id' => $class->id]))
            ->assertOk()
            ->assertDontSee('No grading scheme is configured')
            ->assertSee('Written Work');
    }
}
