<?php

namespace Tests\Feature;

use App\Models\GradeSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Principal → Settings → Grades → Student Grade: two per-school switches that
 * gate the student "Grades" (report card) and "Form 137" (transcript) views.
 *
 * Grade-visibility is student-facing, so this covers the default-off block, the
 * enable path, basic-ed scoping, tenant isolation, and the role gate on saving.
 * The sidebar hides the same two items using the same flags + isBasicEd() the
 * middleware enforces, so the gate is the thing worth pinning down here.
 */
class StudentGradeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** A basic-ed student (active enrollment) so the grade-view gate applies. */
    private function basicEdStudent(School $school): User
    {
        $user = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);

        $student = Student::create([
            'school_id' => $school->id, 'user_id' => $user->id,
            'student_number' => 'S-'.$user->id, 'first_name' => 'A', 'last_name' => 'B',
        ]);

        $ayId = DB::table('academic_years')->insertGetId([
            'school_id' => $school->id, 'name' => '2026-2027', 'is_active' => true,
        ]);

        $termId = DB::table('terms')->insertGetId([
            'school_id' => $school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'name' => 'Basic Ed 2026-2027', 'education_level' => 'basic_ed',
            'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);

        StudentEnrollment::create([
            'school_id' => $school->id, 'student_id' => $student->id,
            'academic_year_id' => $ayId, 'term_id' => $termId,
            'education_level' => 'elementary', 'status' => 'enrolled',
        ]);

        return $user;
    }

    public function test_defaults_are_off_and_both_views_are_blocked(): void
    {
        $school = School::factory()->create();

        // The freshly-created settings row hides both views.
        $setting = GradeSetting::forSchool($school->id);
        $this->assertFalse($setting->show_student_grades);
        $this->assertFalse($setting->show_student_form137);

        $student = $this->basicEdStudent($school);

        $this->actingAs($student)->get(route('student.report-card'))->assertForbidden();
        $this->actingAs($student)->get(route('student.transcript.index'))->assertForbidden();
    }

    public function test_principal_enables_the_views_and_students_can_open_them(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);

        $this->actingAs($principal)->post(route('principal.settings.grades.student-visibility'), [
            'show_student_grades' => '1',
            'show_student_form137' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('grade_settings', [
            'school_id' => $school->id, 'show_student_grades' => 1, 'show_student_form137' => 1,
        ]);

        $student = $this->basicEdStudent($school);

        // The gate lets them through (downstream render is out of scope here).
        $this->assertNotSame(403, $this->actingAs($student)->get(route('student.report-card'))->status());
        $this->assertNotSame(403, $this->actingAs($student)->get(route('student.transcript.index'))->status());
    }

    public function test_unchecked_switches_save_as_off(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);

        GradeSetting::forSchool($school->id)->update([
            'show_student_grades' => true, 'show_student_form137' => true,
        ]);

        // A checkbox that is off is simply absent from the payload.
        $this->actingAs($principal)->post(route('principal.settings.grades.student-visibility'), [])
            ->assertRedirect();

        $this->assertDatabaseHas('grade_settings', [
            'school_id' => $school->id, 'show_student_grades' => 0, 'show_student_form137' => 0,
        ]);
    }

    public function test_non_basic_ed_student_is_not_gated(): void
    {
        $school = School::factory()->create();

        // No Student row / enrollment → isBasicEd() is false → the gate is a no-op.
        $student = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);

        $this->assertNotSame(403, $this->actingAs($student)->get(route('student.report-card'))->status());
    }

    public function test_the_switch_is_scoped_to_its_own_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        // School A enables everything; School B keeps the defaults (off).
        GradeSetting::forSchool($schoolA->id)->update([
            'show_student_grades' => true, 'show_student_form137' => true,
        ]);

        $studentB = $this->basicEdStudent($schoolB);

        $this->actingAs($studentB)->get(route('student.report-card'))->assertForbidden();
        $this->actingAs($studentB)->get(route('student.transcript.index'))->assertForbidden();
    }

    public function test_a_student_cannot_change_the_setting(): void
    {
        $school = School::factory()->create();
        $student = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);

        $this->actingAs($student)->post(route('principal.settings.grades.student-visibility'), [
            'show_student_grades' => '1',
        ])->assertForbidden();

        $this->assertDatabaseMissing('grade_settings', [
            'school_id' => $school->id, 'show_student_grades' => 1,
        ]);
    }
}
