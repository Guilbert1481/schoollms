<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Item #4 — the prepared device seam. An unattended biometric/facial terminal
 * POSTs a sighting; a valid device key + a rostered student at the host's school
 * records attendance through the one ingestion funnel with the device method.
 * A bad/missing key, a non-rostered student, or an identifier from another
 * school records nothing.
 */
class DeviceAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'device-shared-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['attendance.device_secret' => self::KEY]);
    }

    /** A school reachable at its own host, so ResolveSchoolFromHost sets the tenant. */
    private function schoolWithHost(): array
    {
        $host = 'dev-'.uniqid().'.test';

        return [School::factory()->create(['domain' => $host]), $host];
    }

    private function term(School $school): int
    {
        return DB::table('terms')->insertGetId([
            'school_id' => $school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
    }

    private function student(School $school, string $number): int
    {
        return DB::table('students')->insertGetId([
            'school_id' => $school->id, 'student_number' => $number, 'first_name' => 'Ana', 'last_name' => 'Cruz',
        ]);
    }

    /** Higher-ed class with an enrolled student. @return array{int,int,string} [classId, studentId, number] */
    private function sessionScenario(School $school, bool $enroll = true): array
    {
        $termId = $this->term($school);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $school->id, 'name' => '2026-2027']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'BSIT', 'year_level' => 1]);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'IT101', 'name' => 'IT']);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $classId = DB::table('classes')->insertGetId([
            'school_id' => $school->id, 'subject_id' => $subjectId, 'term_id' => $termId,
            'teacher_id' => $teacher->id, 'section_id' => $sectionId, 'code' => 'IT101-A',
        ]);
        $number = 'S-'.uniqid();
        $studentId = $this->student($school, $number);

        if ($enroll) {
            $enrollmentId = DB::table('student_enrollments')->insertGetId([
                'school_id' => $school->id, 'student_id' => $studentId, 'academic_year_id' => $ayId,
                'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled',
            ]);
            DB::table('student_enrollment_subjects')->insert([
                'student_enrollment_id' => $enrollmentId, 'class_id' => $classId, 'subject_id' => $subjectId, 'status' => 'enrolled',
            ]);
        }

        return [$classId, $studentId, $number];
    }

    private function send(string $host, array $payload, ?string $key = self::KEY)
    {
        $headers = $key === null ? [] : ['X-Device-Key' => $key];

        return $this->postJson("http://{$host}/attendance/device/checkin", $payload, $headers);
    }

    /* ------------------------------------------------------------------ */

    public function test_a_biometric_sighting_records_session_attendance(): void
    {
        [$school, $host] = $this->schoolWithHost();
        [$classId, $studentId, $number] = $this->sessionScenario($school);

        $this->send($host, ['context' => "session:{$classId}", 'student_number' => $number, 'method' => 'biometric'])
            ->assertOk()
            ->assertJson(['ok' => true, 'status' => 'present']);

        $this->assertDatabaseHas('attendance_records', [
            'school_id' => $school->id, 'student_id' => $studentId, 'scope' => 'session',
            'class_id' => $classId, 'method' => 'biometric', 'status' => 'present',
        ]);
    }

    public function test_a_facial_sighting_records_daily_attendance(): void
    {
        [$school, $host] = $this->schoolWithHost();
        $termId = $this->term($school);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $school->id, 'name' => '2026-2027']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'G5', 'year_level' => 5]);
        $number = 'S-'.uniqid();
        $studentId = $this->student($school, $number);
        DB::table('student_enrollments')->insert([
            'school_id' => $school->id, 'student_id' => $studentId, 'academic_year_id' => $ayId,
            'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled',
        ]);

        $this->send($host, ['context' => "daily:{$sectionId}", 'student_number' => $number, 'method' => 'facial'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('attendance_records', [
            'school_id' => $school->id, 'student_id' => $studentId, 'scope' => 'daily',
            'section_id' => $sectionId, 'method' => 'facial',
        ]);
    }

    public function test_a_missing_or_wrong_device_key_is_rejected(): void
    {
        [$school, $host] = $this->schoolWithHost();
        [$classId, , $number] = $this->sessionScenario($school);
        $payload = ['context' => "session:{$classId}", 'student_number' => $number, 'method' => 'biometric'];

        $this->send($host, $payload, key: null)->assertStatus(401);
        $this->send($host, $payload, key: 'wrong')->assertStatus(401);

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_a_non_rostered_student_records_nothing(): void
    {
        [$school, $host] = $this->schoolWithHost();
        [$classId, , $number] = $this->sessionScenario($school, enroll: false);

        $this->send($host, ['context' => "session:{$classId}", 'student_number' => $number, 'method' => 'biometric'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_an_identifier_from_another_school_is_not_matched(): void
    {
        [$school, $host] = $this->schoolWithHost();
        [$classId] = $this->sessionScenario($school);

        // A student with this number exists, but at a DIFFERENT school.
        $other = School::factory()->create();
        $foreignNumber = 'S-foreign-'.uniqid();
        $this->student($other, $foreignNumber);

        $this->send($host, ['context' => "session:{$classId}", 'student_number' => $foreignNumber, 'method' => 'biometric'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertDatabaseCount('attendance_records', 0);
    }
}
