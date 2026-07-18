<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Teacher → Attendance → Summary. Attendance feeds the gradebook, so these lock
 * down the two things that would quietly corrupt a report card conversation:
 * which marks land in which bucket, and whose roster a teacher can read.
 */
class AttendanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        $schoolId = DB::table('schools')->insertGetId([
            'school_name' => 'Test School', 'code' => 'TS', 'slug' => 'test-school',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $ayId = DB::table('academic_years')->insertGetId([
            'school_id' => $schoolId, 'name' => '2026-2027', 'education_level' => 'basic_ed',
            'start_date' => '2026-06-01', 'end_date' => '2027-04-30',
            'is_active' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $termId = DB::table('terms')->insertGetId([
            'school_id' => $schoolId, 'education_level' => 'basic_ed', 'academic_year_id' => $ayId,
            'academic_year' => '2026-2027', 'enrollment_type' => 'regular', 'term' => 'Enrollment',
            'name' => 'Basic Ed 2026-2027', 'start_date' => '2026-06-01', 'end_date' => '2027-04-30',
            'status' => 'active', 'is_current' => 1, 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $teacher = $this->user($schoolId, 'teacher', 'adviser@test.local');
        $other = $this->user($schoolId, 'teacher', 'other@test.local');

        $sectionId = DB::table('sections')->insertGetId([
            'school_id' => $schoolId, 'term_id' => $termId, 'name' => 'Patient',
            'year_level' => 5, 'adviser_id' => $teacher, 'capacity' => 40,
            'is_active' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $studentId = DB::table('students')->insertGetId([
            'school_id' => $schoolId, 'first_name' => 'Ana', 'last_name' => 'Reyes',
            'student_number' => 'S-001', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('student_enrollments')->insert([
            'school_id' => $schoolId, 'student_id' => $studentId, 'section_id' => $sectionId,
            'academic_year_id' => $ayId, 'term_id' => $termId, 'status' => 'enrolled',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return compact('schoolId', 'ayId', 'termId', 'teacher', 'other', 'sectionId', 'studentId');
    }

    private function user(int $schoolId, string $role, string $email): int
    {
        return DB::table('users')->insertGetId([
            'school_id' => $schoolId, 'first_name' => 'T', 'last_name' => 'User',
            'email' => $email, 'password' => bcrypt('secret'), 'role' => $role,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function mark(array $ctx, string $date, string $status): void
    {
        DB::table('attendance_records')->insert([
            'school_id' => $ctx['schoolId'], 'student_id' => $ctx['studentId'], 'scope' => 'daily',
            'section_id' => $ctx['sectionId'], 'academic_year_id' => $ctx['ayId'], 'term_id' => $ctx['termId'],
            'attendance_date' => $date, 'status' => $status, 'method' => 'manual',
            'recorded_by' => $ctx['teacher'], 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function summaryFor(array $ctx, string $duration): array
    {
        $service = app(AttendanceSummaryService::class);
        $section = $service->sectionsFor($ctx['teacher'], $ctx['schoolId'])->firstWhere('id', $ctx['sectionId']);

        return $service->summarize($section, AttendanceSummaryService::SOURCE_HOMEROOM, $duration, collect(), $ctx['schoolId']);
    }

    public function test_marks_land_in_the_correct_weekly_bucket_across_a_week_boundary(): void
    {
        $ctx = $this->scenario();

        // 2026-06-14 is a Sunday, 2026-06-15 the Monday that starts the next week.
        $this->travelTo('2026-06-30');
        $this->mark($ctx, '2026-06-14', AttendanceRecord::STATUS_PRESENT);
        $this->mark($ctx, '2026-06-15', AttendanceRecord::STATUS_ABSENT);

        $summary = $this->summaryFor($ctx, AttendanceSummaryService::DURATION_WEEKLY);
        $cells = $summary['rows'][0]['cells'];

        $withMarks = collect($cells)->filter(fn ($c) => $c['marked'] > 0);

        $this->assertCount(2, $withMarks, 'the two dates must fall in two different weeks');
        $this->assertSame(100.0, $withMarks->first()['rate']);
        $this->assertSame(0.0, $withMarks->last()['rate']);
    }

    public function test_rate_follows_the_gradebook_credit_policy(): void
    {
        $ctx = $this->scenario();
        $this->travelTo('2026-06-30');

        // present + late + excused score 1.0 each; half_day 0.5; absent 0.0.
        // 3.5 credit over 5 marks = 70%.
        $this->mark($ctx, '2026-06-01', AttendanceRecord::STATUS_PRESENT);
        $this->mark($ctx, '2026-06-02', AttendanceRecord::STATUS_LATE);
        $this->mark($ctx, '2026-06-03', AttendanceRecord::STATUS_EXCUSED);
        $this->mark($ctx, '2026-06-04', AttendanceRecord::STATUS_HALF_DAY);
        $this->mark($ctx, '2026-06-05', AttendanceRecord::STATUS_ABSENT);

        $summary = $this->summaryFor($ctx, AttendanceSummaryService::DURATION_MONTHLY);
        $total = $summary['rows'][0]['total'];

        $this->assertSame(5, $total['marked']);
        $this->assertSame(70.0, $total['rate']);
        $this->assertSame(1, $total['excused']);
        $this->assertSame(1, $total['half_day']);
    }

    public function test_a_teacher_only_sees_sections_they_advise_or_teach(): void
    {
        $ctx = $this->scenario();
        $service = app(AttendanceSummaryService::class);

        $mine = $service->sectionsFor($ctx['teacher'], $ctx['schoolId']);
        $theirs = $service->sectionsFor($ctx['other'], $ctx['schoolId']);

        $this->assertTrue($mine->contains('id', $ctx['sectionId']));
        $this->assertTrue($theirs->isEmpty(), 'a teacher with no assignment must see no sections');
    }

    public function test_sections_are_scoped_to_the_teachers_own_school(): void
    {
        $ctx = $this->scenario();
        $service = app(AttendanceSummaryService::class);

        $foreignSchool = DB::table('schools')->insertGetId([
            'school_name' => 'Other School', 'code' => 'OS', 'slug' => 'other-school',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertTrue(
            $service->sectionsFor($ctx['teacher'], $foreignSchool)->isEmpty(),
            'the section must not surface under another school id'
        );
    }

    public function test_summary_page_renders_for_an_assigned_teacher(): void
    {
        $ctx = $this->scenario();
        $this->travelTo('2026-06-30');
        $this->mark($ctx, '2026-06-10', AttendanceRecord::STATUS_PRESENT);

        $user = \App\Models\User::find($ctx['teacher']);

        $this->actingAs($user)
            ->get(route('teacher.attendance.summary'))
            ->assertOk()
            ->assertSee('Section trend')
            ->assertSee('Reyes, Ana');
    }
}
