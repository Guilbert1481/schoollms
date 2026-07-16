<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Attendance\QrAttendanceTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Item #3 — the student QR scan endpoint. A valid current token from a rostered
 * student records a QR check-in; an expired token or a non-rostered student
 * records nothing.
 */
class QrScanTest extends TestCase
{
    use RefreshDatabase;

    /** A higher-ed class plus a student-account. Returns [classId, studentUser, student, enrolled?]. */
    private function scenario(School $school, bool $enroll = true): array
    {
        $termId = DB::table('terms')->insertGetId([
            'school_id' => $school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $school->id, 'name' => '2026-2027']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'BSIT', 'year_level' => 1]);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'IT101', 'name' => 'IT']);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $classId = DB::table('classes')->insertGetId([
            'school_id' => $school->id, 'subject_id' => $subjectId, 'term_id' => $termId,
            'teacher_id' => $teacher->id, 'section_id' => $sectionId, 'code' => 'IT101-A',
        ]);

        $studentUser = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);
        $student = Student::create([
            'school_id' => $school->id, 'user_id' => $studentUser->id,
            'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz',
        ]);

        if ($enroll) {
            $enrollmentId = DB::table('student_enrollments')->insertGetId([
                'school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId,
                'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled',
            ]);
            DB::table('student_enrollment_subjects')->insert([
                'student_enrollment_id' => $enrollmentId, 'class_id' => $classId, 'subject_id' => $subjectId, 'status' => 'enrolled',
            ]);
        }

        return [$classId, $studentUser, $student, $teacher];
    }

    private function token(string $context): string
    {
        return app(QrAttendanceTokenService::class)->token($context, QrAttendanceTokenService::DEFAULT_WINDOW);
    }

    public function test_a_valid_scan_records_qr_attendance(): void
    {
        $school = School::factory()->create();
        [$classId, $studentUser, $student] = $this->scenario($school);
        $context = "session:{$classId}";

        $this->actingAs($studentUser)
            ->get(route('attendance.scan', ['c' => $context, 't' => $this->token($context)]))
            ->assertOk()
            ->assertSee('Checked in');

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id, 'scope' => 'session', 'class_id' => $classId, 'method' => 'qr', 'status' => 'present',
        ]);
    }

    public function test_an_expired_or_bad_token_records_nothing(): void
    {
        $school = School::factory()->create();
        [$classId, $studentUser] = $this->scenario($school);

        $this->actingAs($studentUser)
            ->get(route('attendance.scan', ['c' => "session:{$classId}", 't' => 'not-a-valid-token']))
            ->assertOk()
            ->assertSee('expired');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_a_non_rostered_student_is_rejected(): void
    {
        $school = School::factory()->create();
        [$classId, $studentUser] = $this->scenario($school, enroll: false);
        $context = "session:{$classId}";

        $this->actingAs($studentUser)
            ->get(route('attendance.scan', ['c' => $context, 't' => $this->token($context)]))
            ->assertOk()
            ->assertSee('not enrolled');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_teacher_live_qr_returns_a_scan_url_for_their_own_class(): void
    {
        $school = School::factory()->create();
        [$classId, , , $teacher] = $this->scenario($school);

        $this->actingAs($teacher)
            ->getJson(route('teacher.attendance.live.qr', ['type' => 'session', 'class_id' => $classId]))
            ->assertOk()
            ->assertJsonStructure(['url', 'window'])
            ->assertJsonFragment(['window' => 10]);
    }

    public function test_teacher_live_qr_is_denied_for_a_class_they_do_not_teach(): void
    {
        $school = School::factory()->create();
        [$classId] = $this->scenario($school);
        $other = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);

        $this->actingAs($other)
            ->getJson(route('teacher.attendance.live.qr', ['type' => 'session', 'class_id' => $classId]))
            ->assertNotFound();
    }
}
