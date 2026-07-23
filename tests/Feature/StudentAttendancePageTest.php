<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Academics → Attendance: the student's own month-by-month attendance history
 * (the sidebar item used to be a dead '#'). Present/late/excused count as
 * attended in the rate, matching the dashboard logic.
 */
class StudentAttendancePageTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $studentUser;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $this->student = Student::create(['school_id' => $this->school->id, 'user_id' => $this->studentUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);
    }

    private function record(string $date, string $status, array $overrides = []): void
    {
        DB::table('attendance_records')->insert(array_merge([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'scope' => 'daily', 'attendance_date' => $date, 'status' => $status,
            'method' => 'manual', 'created_at' => now(), 'updated_at' => now(),
        ], $overrides));
    }

    public function test_attendance_page_shows_records_counts_and_rate(): void
    {
        $month = now()->format('Y-m');
        $this->record($month.'-02', 'present', ['time_in' => '07:45:00']);
        $this->record($month.'-03', 'present');
        $this->record($month.'-04', 'absent');

        $this->actingAs($this->studentUser)
            ->get(route('student.attendance.index'))
            ->assertOk()
            ->assertSee('My Attendance')
            ->assertSee('67%')          // 2 of 3 attended
            ->assertSee('Present')
            ->assertSee('Absent')
            ->assertSee('7:45 AM');
    }

    public function test_month_filter_scopes_the_records(): void
    {
        $lastMonth = now()->subMonthNoOverflow();
        $this->record($lastMonth->format('Y-m').'-10', 'late');

        // Default month (current) has nothing.
        $this->actingAs($this->studentUser)
            ->get(route('student.attendance.index'))
            ->assertOk()
            ->assertSee('No attendance records');

        // Selecting last month shows the row.
        $this->actingAs($this->studentUser)
            ->get(route('student.attendance.index', ['month' => $lastMonth->format('Y-m')]))
            ->assertOk()
            ->assertSee('Late');
    }

    public function test_status_filter_narrows_rows_but_summary_stays_month_wide(): void
    {
        $month = now()->format('Y-m');
        $this->record($month.'-02', 'present');
        $this->record($month.'-03', 'absent', ['remarks' => 'Sick day']);

        $this->actingAs($this->studentUser)
            ->get(route('student.attendance.index', ['status' => 'absent']))
            ->assertOk()
            ->assertSee('Sick day')
            ->assertSee('50%');         // rate still computed over the whole month
    }
}
