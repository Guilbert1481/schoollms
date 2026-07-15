<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Services\Attendance\AttendanceRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3b — attendance rate that feeds the grading engine. Pins the status
 * credit policy (present/late full, half-day half, excused neutral, absent
 * zero) and the daily vs session denominators.
 */
class AttendanceRateTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceRateService $rates;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rates = new AttendanceRateService;
    }

    private function school(): School
    {
        return School::factory()->create();
    }

    private function student(int $schoolId): Student
    {
        return Student::create([
            'school_id' => $schoolId, 'student_number' => 'S-'.uniqid(), 'first_name' => 'A', 'last_name' => 'B',
        ]);
    }

    private function term(int $schoolId): int
    {
        return DB::table('terms')->insertGetId([
            'school_id' => $schoolId, 'academic_year' => '2026-2027', 'enrollment_type' => 'basic_ed',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
    }

    private function markDaily(int $schoolId, int $studentId, int $sectionId, string $date, string $status): void
    {
        DB::table('attendance_records')->insert([
            'school_id' => $schoolId, 'student_id' => $studentId, 'scope' => 'daily',
            'section_id' => $sectionId, 'attendance_date' => $date, 'status' => $status, 'method' => 'manual',
        ]);
    }

    private function markSession(int $schoolId, int $studentId, int $classId, string $date, string $status): void
    {
        DB::table('attendance_records')->insert([
            'school_id' => $schoolId, 'student_id' => $studentId, 'scope' => 'session',
            'class_id' => $classId, 'attendance_date' => $date, 'status' => $status, 'method' => 'manual',
        ]);
    }

    public function test_daily_rate_over_expected_days(): void
    {
        $school = $this->school();
        $student = $this->student($school->id);
        $termId = $this->term($school->id);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'G5']);

        // present + present + late + absent = credit 3 over 5 expected days = 60%.
        $this->markDaily($school->id, $student->id, $sectionId, '2026-06-01', 'present');
        $this->markDaily($school->id, $student->id, $sectionId, '2026-06-02', 'present');
        $this->markDaily($school->id, $student->id, $sectionId, '2026-06-03', 'late');
        $this->markDaily($school->id, $student->id, $sectionId, '2026-06-04', 'absent');

        $this->assertEqualsWithDelta(60.0, $this->rates->dailyRate($student->id, $sectionId, 5), 0.001);
    }

    public function test_half_day_counts_half_and_excused_is_neutral(): void
    {
        $school = $this->school();
        $student = $this->student($school->id);
        $termId = $this->term($school->id);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'G5']);

        // present(1) + half_day(0.5) + excused(1) = 2.5 over 5 = 50%.
        $this->markDaily($school->id, $student->id, $sectionId, '2026-06-01', 'present');
        $this->markDaily($school->id, $student->id, $sectionId, '2026-06-02', 'half_day');
        $this->markDaily($school->id, $student->id, $sectionId, '2026-06-03', 'excused');

        $this->assertEqualsWithDelta(50.0, $this->rates->dailyRate($student->id, $sectionId, 5), 0.001);
    }

    public function test_daily_rate_is_capped_at_100(): void
    {
        $school = $this->school();
        $student = $this->student($school->id);
        $termId = $this->term($school->id);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'G5']);

        // 3 present but only 2 expected days → capped at 100, not 150.
        foreach (['2026-06-01', '2026-06-02', '2026-06-03'] as $d) {
            $this->markDaily($school->id, $student->id, $sectionId, $d, 'present');
        }

        $this->assertEqualsWithDelta(100.0, $this->rates->dailyRate($student->id, $sectionId, 2), 0.001);
    }

    public function test_daily_rate_is_null_without_expected_days(): void
    {
        $school = $this->school();
        $student = $this->student($school->id);

        $this->assertNull($this->rates->dailyRate($student->id, 1, 0));
    }

    public function test_session_rate_over_held_sessions(): void
    {
        $school = $this->school();
        $student = $this->student($school->id);
        $termId = $this->term($school->id);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'BSIT-1A']);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'IT101', 'name' => 'IT']);
        $teacherId = \App\Models\User::factory()->create(['school_id' => $school->id, 'role' => 'teacher'])->id;
        $classId = DB::table('classes')->insertGetId([
            'school_id' => $school->id, 'subject_id' => $subjectId, 'term_id' => $termId,
            'teacher_id' => $teacherId, 'section_id' => $sectionId, 'code' => 'IT101-A',
        ]);

        // present + present + absent + late = credit 3 over 4 held = 75%.
        $this->markSession($school->id, $student->id, $classId, '2026-06-01', 'present');
        $this->markSession($school->id, $student->id, $classId, '2026-06-02', 'present');
        $this->markSession($school->id, $student->id, $classId, '2026-06-03', 'absent');
        $this->markSession($school->id, $student->id, $classId, '2026-06-04', 'late');

        $this->assertEqualsWithDelta(75.0, $this->rates->sessionRate($student->id, $classId), 0.001);
    }

    public function test_session_rate_is_null_without_records(): void
    {
        $school = $this->school();
        $student = $this->student($school->id);

        $this->assertNull($this->rates->sessionRate($student->id, 999));
    }
}
