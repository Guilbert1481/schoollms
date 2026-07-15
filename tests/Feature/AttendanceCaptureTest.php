<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Attendance\AttendanceIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 1 attendance capture: a teacher takes DAILY attendance for an advisory
 * section or SESSION attendance for a class, always through the one ingestion
 * funnel. Attendance feeds grades downstream, so this guards the write path:
 * ownership, roster membership, tenant scoping, and idempotent upsert.
 */
class AttendanceCaptureTest extends TestCase
{
    use RefreshDatabase;

    private function academicYear(School $school): int
    {
        return DB::table('academic_years')->insertGetId([
            'school_id' => $school->id,
            'name' => '2026-2027',
        ]);
    }

    private function term(School $school): int
    {
        return DB::table('terms')->insertGetId([
            'school_id' => $school->id,
            'academic_year' => '2026-2027',
            'enrollment_type' => 'basic_ed',
            'term' => 'Full Year',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
        ]);
    }

    private function student(School $school, string $number, string $first, string $last): Student
    {
        return Student::create([
            'school_id' => $school->id,
            'student_number' => $number,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    /* ---------------------------------------------------------------- Daily */

    public function test_daily_attendance_is_saved_for_an_advisory_section(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $termId = $this->term($school);
        $ayId = $this->academicYear($school);
        $section = Section::create(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'Grade 5 - Rizal', 'adviser_id' => $teacher->id]);
        $student = $this->student($school, 'S-001', 'Ana', 'Cruz');
        StudentEnrollment::create(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $section->id, 'status' => 'enrolled']);

        $this->actingAs($teacher)
            ->post(route('teacher.attendance.store'), [
                'type' => 'daily',
                'date' => '2026-07-14',
                'section_id' => $section->id,
                'marks' => [
                    ['student_id' => $student->id, 'status' => 'late', 'time_in' => '08:15'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('attendance_records', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'scope' => 'daily',
            'section_id' => $section->id,
            'class_id' => null,
            'status' => 'late',
            'method' => 'manual',
            'recorded_by' => $teacher->id,
        ]);
    }

    public function test_saving_is_idempotent_and_updates_existing_marks(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $termId = $this->term($school);
        $ayId = $this->academicYear($school);
        $section = Section::create(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'G5', 'adviser_id' => $teacher->id]);
        $student = $this->student($school, 'S-010', 'Ben', 'Reyes');
        StudentEnrollment::create(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $section->id, 'status' => 'enrolled']);

        $payload = fn (string $status) => [
            'type' => 'daily', 'date' => '2026-07-14', 'section_id' => $section->id,
            'marks' => [['student_id' => $student->id, 'status' => $status]],
        ];

        $this->actingAs($teacher)->post(route('teacher.attendance.store'), $payload('present'))->assertRedirect();
        $this->actingAs($teacher)->post(route('teacher.attendance.store'), $payload('absent'))->assertRedirect();

        $rows = AttendanceRecord::where('student_id', $student->id)->whereDate('attendance_date', '2026-07-14')->get();
        $this->assertCount(1, $rows, 'Re-saving the same student/date must update, not duplicate.');
        $this->assertSame('absent', $rows->first()->status);
    }

    public function test_a_teacher_cannot_mark_a_section_they_do_not_advise(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $otherTeach = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $termId = $this->term($school);
        $foreign = Section::create(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'Not Mine', 'adviser_id' => $otherTeach->id]);

        $this->actingAs($teacher)
            ->post(route('teacher.attendance.store'), [
                'type' => 'daily', 'date' => '2026-07-14', 'section_id' => $foreign->id,
                'marks' => [['student_id' => 1, 'status' => 'present']],
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_students_not_on_the_roster_are_ignored(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $termId = $this->term($school);
        $ayId = $this->academicYear($school);
        $section = Section::create(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'G5', 'adviser_id' => $teacher->id]);
        $onRoster = $this->student($school, 'S-100', 'On', 'Roster');
        $intruder = $this->student($school, 'S-200', 'Off', 'Roster');
        StudentEnrollment::create(['school_id' => $school->id, 'student_id' => $onRoster->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $section->id, 'status' => 'enrolled']);

        $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
            'type' => 'daily', 'date' => '2026-07-14', 'section_id' => $section->id,
            'marks' => [
                ['student_id' => $onRoster->id, 'status' => 'present'],
                ['student_id' => $intruder->id, 'status' => 'present'], // not enrolled here
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_records', ['student_id' => $onRoster->id]);
        $this->assertDatabaseMissing('attendance_records', ['student_id' => $intruder->id]);
    }

    /* -------------------------------------------------------------- Session */

    public function test_session_attendance_is_saved_for_a_class(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $termId = $this->term($school);
        $section = Section::create(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'BSIT-1A']);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'IT101', 'name' => 'Intro to IT']);
        // Insert via query builder: ClassModel::$fillable still carries the
        // legacy `semester_id` instead of the real `term_id` column, so mass
        // assignment would silently drop term_id.
        $classId = DB::table('classes')->insertGetId(['school_id' => $school->id, 'subject_id' => $subjectId, 'term_id' => $termId, 'teacher_id' => $teacher->id, 'section_id' => $section->id, 'code' => 'IT101-A']);
        $class = ClassModel::findOrFail($classId);
        $student = $this->student($school, 'S-300', 'Cara', 'Lim');
        $class->students()->attach($student->id, ['status' => 'enrolled']);

        $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
            'type' => 'session', 'date' => '2026-07-14', 'class_id' => $class->id,
            'marks' => [['student_id' => $student->id, 'status' => 'present', 'time_in' => '09:00']],
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'scope' => 'session',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'status' => 'present',
        ]);
    }

    /* -------------------------------------------------------------- Service */

    public function test_ingestion_service_derives_status_from_time_in(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $termId = $this->term($school);
        $section = Section::create(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'G6', 'adviser_id' => $teacher->id]);
        $present = $this->student($school, 'S-400', 'Has', 'Timein');
        $absent = $this->student($school, 'S-401', 'No', 'Timein');

        $this->actingAs($teacher);
        $service = app(AttendanceIngestionService::class);

        $withTime = $service->record(['scope' => 'daily', 'student_id' => $present->id, 'section_id' => $section->id, 'attendance_date' => '2026-07-14', 'time_in' => '07:50']);
        $without = $service->record(['scope' => 'daily', 'student_id' => $absent->id, 'section_id' => $section->id, 'attendance_date' => '2026-07-14']);

        $this->assertSame('present', $withTime->status);
        $this->assertSame('absent', $without->status);
        $this->assertSame($school->id, $withTime->school_id, 'BelongsToSchool must stamp the tenant.');
    }
}
