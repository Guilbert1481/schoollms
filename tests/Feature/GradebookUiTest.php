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
 * Phase 3c UI — the teacher gradebook web flow: open a class, save draft scores,
 * post finals. Guards the ownership check and the no-scheme case. The grade math
 * itself is pinned in GradebookTest / GradingEngineTest.
 */
class GradebookUiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A higher-ed class the given teacher teaches, with a WW/PT/QA scheme and one
     * enrolled student. Returns [class, student, componentIdsByName].
     */
    private function setupClass(School $school, User $teacher, int $yearLevel = 1, bool $withScheme = true): array
    {
        $termId = DB::table('terms')->insertGetId([
            'school_id' => $school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $school->id, 'name' => '2026-2027']);

        $componentIds = collect();
        if ($withScheme) {
            $levelId = DB::table('academic_levels')->insertGetId([
                'school_id' => $school->id, 'name' => 'Year '.$yearLevel, 'sequence_order' => $yearLevel, 'type' => 'higher',
            ]);
            $setting = GradingSetting::create([
                'school_id' => $school->id, 'academic_level_id' => $levelId,
                'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0,
            ]);
            foreach ([['WW', 30], ['PT', 40], ['QA', 20]] as $i => [$name, $w]) {
                GradeComponent::create(['school_id' => $school->id, 'grading_setting_id' => $setting->id, 'name' => $name, 'weight' => $w, 'sort_order' => $i]);
            }
            $componentIds = $setting->components()->pluck('id', 'name');
        }

        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'BSIT', 'year_level' => $yearLevel]);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'IT101', 'name' => 'Intro IT']);
        $classId = DB::table('classes')->insertGetId([
            'school_id' => $school->id, 'subject_id' => $subjectId, 'term_id' => $termId,
            'teacher_id' => $teacher->id, 'section_id' => $sectionId, 'code' => 'IT101-A',
        ]);

        $student = Student::create(['school_id' => $school->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId,
            'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled',
        ]);
        DB::table('student_enrollment_subjects')->insert([
            'student_enrollment_id' => $enrollmentId, 'class_id' => $classId, 'subject_id' => $subjectId, 'status' => 'enrolled',
        ]);

        return [ClassModel::findOrFail($classId), $student, $componentIds];
    }

    public function test_teacher_opens_the_gradebook_for_a_class(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        [$class, $student] = $this->setupClass($school, $teacher);

        $this->actingAs($teacher)
            ->get(route('teacher.gradebook.index', ['class_id' => $class->id]))
            ->assertOk()
            ->assertSee('Cruz, Ana')
            ->assertSee('WW');
    }

    public function test_draft_saves_scores_and_post_writes_the_final(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        [$class, $student, $ids] = $this->setupClass($school, $teacher);

        $scores = ['scores' => [$student->id => [$ids['WW'] => 90, $ids['PT'] => 80, $ids['QA'] => 70]], 'class_id' => $class->id];

        // Draft: scores stored, nothing posted.
        $this->actingAs($teacher)->post(route('teacher.gradebook.draft'), $scores)->assertRedirect();
        $this->assertDatabaseCount('component_scores', 3);
        $this->assertNull(DB::table('student_enrollment_subjects')->where('class_id', $class->id)->value('final_grade'));

        // Post: final computed (81.11) and written.
        $this->actingAs($teacher)->post(route('teacher.gradebook.post'), $scores)->assertRedirect()->assertSessionHas('status');
        $ses = DB::table('student_enrollment_subjects')->where('class_id', $class->id)->first();
        $this->assertEqualsWithDelta(81.11, (float) $ses->final_grade, 0.01);
        $this->assertSame('passed', $ses->status);
    }

    public function test_a_teacher_cannot_post_to_a_class_they_do_not_teach(): void
    {
        $school = School::factory()->create();
        $owner = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $intruder = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        [$class] = $this->setupClass($school, $owner);

        $this->actingAs($intruder)
            ->post(route('teacher.gradebook.post'), ['class_id' => $class->id, 'scores' => []])
            ->assertNotFound();
    }

    public function test_a_class_without_a_scheme_shows_a_warning(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        // year level 9 → no matching higher-ed academic level / scheme.
        [$class] = $this->setupClass($school, $teacher, yearLevel: 9, withScheme: false);

        $this->actingAs($teacher)
            ->get(route('teacher.gradebook.index', ['class_id' => $class->id]))
            ->assertOk()
            ->assertSee('No grading scheme is configured');
    }
}
