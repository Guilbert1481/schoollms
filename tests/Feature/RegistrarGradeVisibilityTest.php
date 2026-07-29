<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Registrar-owned, per-student grade-view visibility (Transcript of Records).
 * A student sees "Grades" (report card) / "Form 137" (transcript) only when the
 * registrar has enabled the matching flag — default OFF.
 *
 * The key change from the old per-school toggle: enforcement is per-student and
 * applies to EVERY student — basic ed or not, enrolled or not. That closes the
 * leak where a student without an active enrollment was never gated and kept
 * seeing both views regardless of the switch.
 */
class RegistrarGradeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** A student user + Student row, with optional visibility flags. */
    private function student(School $school, array $flags = []): User
    {
        $user = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);

        Student::create(array_merge([
            'school_id' => $school->id, 'user_id' => $user->id,
            'student_number' => 'S-'.$user->id, 'first_name' => 'A', 'last_name' => 'B',
        ], $flags));

        return $user;
    }

    public function test_defaults_off_block_both_views_even_without_an_enrollment(): void
    {
        $school = School::factory()->create();
        // No enrollment at all — exactly the case the old gate leaked through.
        $student = $this->student($school);

        $this->actingAs($student)->get(route('student.report-card'))->assertForbidden();
        $this->actingAs($student)->get(route('student.transcript.index'))->assertForbidden();
    }

    public function test_registrar_grant_opens_the_matching_view_only(): void
    {
        $school = School::factory()->create();
        $registrar = User::factory()->create(['school_id' => $school->id, 'role' => 'registrar']);
        $studentUser = $this->student($school);
        $student = Student::where('user_id', $studentUser->id)->firstOrFail();

        $this->actingAs($registrar)->post(route('registrar.transcripts.grade-visibility', $student->id), [
            'view' => 'grades', 'enabled' => 1,
        ])->assertOk()->assertJson(['ok' => true, 'enabled' => true]);

        $this->assertDatabaseHas('students', ['id' => $student->id, 'show_grades' => 1, 'show_form137' => 0]);

        // Grades opens; Form 137 stays blocked (only the granted view opens).
        $this->assertNotSame(403, $this->actingAs($studentUser)->get(route('student.report-card'))->status());
        $this->actingAs($studentUser)->get(route('student.transcript.index'))->assertForbidden();
    }

    public function test_registrar_can_toggle_a_granted_view_back_off(): void
    {
        $school = School::factory()->create();
        $registrar = User::factory()->create(['school_id' => $school->id, 'role' => 'registrar']);
        $studentUser = $this->student($school, ['show_form137' => true]);
        $student = Student::where('user_id', $studentUser->id)->firstOrFail();

        $this->actingAs($registrar)->post(route('registrar.transcripts.grade-visibility', $student->id), [
            'view' => 'form137', 'enabled' => 0,
        ])->assertOk()->assertJson(['enabled' => false]);

        $this->assertDatabaseHas('students', ['id' => $student->id, 'show_form137' => 0]);
        $this->actingAs($studentUser)->get(route('student.transcript.index'))->assertForbidden();
    }

    public function test_a_registrar_cannot_toggle_another_schools_student(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $registrarA = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'registrar']);
        $studentBUser = $this->student($schoolB);
        $studentB = Student::where('user_id', $studentBUser->id)->firstOrFail();

        $this->actingAs($registrarA)->post(route('registrar.transcripts.grade-visibility', $studentB->id), [
            'view' => 'grades', 'enabled' => 1,
        ])->assertNotFound();

        $this->assertDatabaseHas('students', ['id' => $studentB->id, 'show_grades' => 0]);
    }

    public function test_a_student_cannot_change_their_own_visibility(): void
    {
        $school = School::factory()->create();
        $studentUser = $this->student($school);
        $student = Student::where('user_id', $studentUser->id)->firstOrFail();

        $this->actingAs($studentUser)->post(route('registrar.transcripts.grade-visibility', $student->id), [
            'view' => 'grades', 'enabled' => 1,
        ])->assertForbidden();

        $this->assertDatabaseHas('students', ['id' => $student->id, 'show_grades' => 0]);
    }

    public function test_the_transcript_list_renders_the_visibility_toggles(): void
    {
        $school = School::factory()->create();
        $registrar = User::factory()->create(['school_id' => $school->id, 'role' => 'registrar']);

        $studentUser = $this->student($school);
        $student = Student::where('user_id', $studentUser->id)->firstOrFail();

        // The master list only shows students with an enrolled/provisional
        // latest enrollment, so give this one a minimal enrolled record.
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $school->id, 'name' => '2026-2027', 'is_active' => 1]);
        $termId = DB::table('terms')->insertGetId([
            'school_id' => $school->id, 'academic_year_id' => $ayId, 'academic_year' => '2026-2027',
            'enrollment_type' => 'x', 'term' => 'first', 'name' => 'T',
            'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
        DB::table('student_enrollments')->insert([
            'school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId,
            'term_id' => $termId, 'status' => 'enrolled', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($registrar)->get(route('registrar.transcripts.index'))
            ->assertOk()
            ->assertSee('gv-switch');
    }

    private function activeYearTerm(School $school): array
    {
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $school->id, 'name' => '2026-2027', 'is_active' => 1]);
        $termId = DB::table('terms')->insertGetId([
            'school_id' => $school->id, 'academic_year_id' => $ayId, 'academic_year' => '2026-2027',
            'enrollment_type' => 'x', 'term' => 'first', 'name' => 'T',
            'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);

        return [$ayId, $termId];
    }

    /** A student with an enrolled latest enrollment, so it shows on the master list. */
    private function enrolledStudent(School $school, int $ayId, int $termId, ?int $yearLevel = null): Student
    {
        $user = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);
        $student = Student::create([
            'school_id' => $school->id, 'user_id' => $user->id,
            'student_number' => 'S-'.$user->id, 'first_name' => 'A', 'last_name' => 'B'.$user->id,
        ]);
        DB::table('student_enrollments')->insert([
            'school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId,
            'term_id' => $termId, 'status' => 'enrolled', 'year_level' => $yearLevel,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $student;
    }

    public function test_bulk_grants_grades_to_all_shown_students(): void
    {
        $school = School::factory()->create();
        $registrar = User::factory()->create(['school_id' => $school->id, 'role' => 'registrar']);
        [$ayId, $termId] = $this->activeYearTerm($school);
        $s1 = $this->enrolledStudent($school, $ayId, $termId);
        $s2 = $this->enrolledStudent($school, $ayId, $termId);

        // No filters → every shown (school) student.
        $this->actingAs($registrar)->post(route('registrar.transcripts.grade-visibility.bulk'), [
            'view' => 'grades', 'enabled' => 1,
        ])->assertOk()->assertJson(['ok' => true, 'count' => 2]);

        $this->assertDatabaseHas('students', ['id' => $s1->id, 'show_grades' => 1, 'show_form137' => 0]);
        $this->assertDatabaseHas('students', ['id' => $s2->id, 'show_grades' => 1]);
    }

    public function test_bulk_respects_the_active_filters(): void
    {
        $school = School::factory()->create();
        $registrar = User::factory()->create(['school_id' => $school->id, 'role' => 'registrar']);
        [$ayId, $termId] = $this->activeYearTerm($school);
        $y1 = $this->enrolledStudent($school, $ayId, $termId, 1);
        $y2 = $this->enrolledStudent($school, $ayId, $termId, 2);

        // Filter to Year 1 → only that student is toggled (proves "apply to shown").
        $this->actingAs($registrar)->post(route('registrar.transcripts.grade-visibility.bulk'), [
            'view' => 'grades', 'enabled' => 1, 'year_level' => '1',
        ])->assertOk()->assertJson(['count' => 1]);

        $this->assertDatabaseHas('students', ['id' => $y1->id, 'show_grades' => 1]);
        $this->assertDatabaseHas('students', ['id' => $y2->id, 'show_grades' => 0]);
    }

    public function test_bulk_is_scoped_to_the_registrars_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $registrarA = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'registrar']);
        [$ayA, $termA] = $this->activeYearTerm($schoolA);
        [$ayB, $termB] = $this->activeYearTerm($schoolB);
        $sA = $this->enrolledStudent($schoolA, $ayA, $termA);
        $sB = $this->enrolledStudent($schoolB, $ayB, $termB);

        $this->actingAs($registrarA)->post(route('registrar.transcripts.grade-visibility.bulk'), [
            'view' => 'grades', 'enabled' => 1,
        ])->assertOk();

        $this->assertDatabaseHas('students', ['id' => $sA->id, 'show_grades' => 1]);
        $this->assertDatabaseHas('students', ['id' => $sB->id, 'show_grades' => 0]); // untouched
    }

    public function test_a_student_cannot_bulk_toggle(): void
    {
        $school = School::factory()->create();
        $studentUser = $this->student($school);

        $this->actingAs($studentUser)->post(route('registrar.transcripts.grade-visibility.bulk'), [
            'view' => 'grades', 'enabled' => 1,
        ])->assertForbidden();
    }
}
