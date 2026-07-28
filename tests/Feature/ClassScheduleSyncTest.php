<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Academics\ClassScheduleSync;
use App\Services\Dashboard\StudentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Scheduler → class_schedules bridge. The Scheduler stores generated
 * timetables in schedules/schedule_sessions keyed by section+subject with
 * lowercase day names; the student timetable reads class_schedules keyed by
 * class with a capitalised day enum. The sync resolves each session to its
 * class via the classes unique key and replaces that class's weekly pattern.
 */
class ClassScheduleSyncTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $studentUser;

    private Student $student;

    private int $termId;

    private int $sectionId;

    private int $subjectId;

    private int $classId;

    private int $scheduleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $this->student = Student::create([
            'school_id' => $this->school->id, 'user_id' => $this->studentUser->id,
            'student_number' => 'S-'.uniqid(), 'first_name' => 'Psalms', 'last_name' => 'Jabinar',
        ]);

        $ayId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => 1,
            'education_level' => 'basic_ed',
        ]);
        $this->termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year_id' => $ayId, 'academic_year' => '2026-2027',
            'enrollment_type' => 'x', 'term' => 'first', 'name' => 'Basic Ed (AY 2026 - 2027)',
            'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
            'education_level' => 'basic_ed', 'status' => 'active',
        ]);
        $this->sectionId = DB::table('sections')->insertGetId([
            'school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'G6-A', 'year_level' => 6,
        ]);
        $this->subjectId = DB::table('subjects')->insertGetId([
            'school_id' => $this->school->id, 'code' => 'SCI', 'name' => 'Science',
        ]);
        $this->classId = DB::table('classes')->insertGetId([
            'school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $this->termId,
            'teacher_id' => $this->teacher->id, 'section_id' => $this->sectionId, 'code' => 'SCI-6A',
        ]);
        DB::table('student_enrollments')->insert([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'academic_year_id' => $ayId, 'term_id' => $this->termId, 'section_id' => $this->sectionId,
            'status' => 'enrolled', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->scheduleId = DB::table('schedules')->insertGetId([
            'school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'Generated Schedule',
            'version' => 1, 'score' => 0, 'is_active' => 1, 'meta' => '{}',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function scheduleSession(array $overrides = []): void
    {
        DB::table('schedule_sessions')->insert(array_merge([
            'schedule_id' => $this->scheduleId, 'section_id' => $this->sectionId,
            'subject_id' => $this->subjectId, 'teacher_id' => $this->teacher->id,
            'day_of_week' => 'monday', 'start_time' => '08:00:00', 'end_time' => '09:00:00',
            'status' => 'valid', 'conflict_reasons' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ], $overrides));
    }

    public function test_sync_maps_sessions_to_class_schedules_and_replaces_on_rerun(): void
    {
        $this->scheduleSession();

        $written = app(ClassScheduleSync::class)->sync($this->scheduleId);

        $this->assertSame(1, $written);
        $this->assertDatabaseHas('class_schedules', [
            'class_id' => $this->classId, 'day_of_week' => 'Monday',
            'start_time' => '08:00:00', 'end_time' => '09:00:00',
        ]);

        // Re-running replaces instead of duplicating.
        app(ClassScheduleSync::class)->sync($this->scheduleId);
        $this->assertSame(1, DB::table('class_schedules')->where('class_id', $this->classId)->count());
    }

    public function test_conflict_and_unmatched_sessions_are_skipped(): void
    {
        $this->scheduleSession();
        $this->scheduleSession(['day_of_week' => 'tuesday', 'status' => 'conflict']);
        $orphanSubject = DB::table('subjects')->insertGetId([
            'school_id' => $this->school->id, 'code' => 'MUS', 'name' => 'Music',
        ]);
        $this->scheduleSession(['day_of_week' => 'wednesday', 'subject_id' => $orphanSubject]);

        $written = app(ClassScheduleSync::class)->sync($this->scheduleId);

        $this->assertSame(1, $written);
        $this->assertSame(1, DB::table('class_schedules')->count());
    }

    public function test_student_weekly_schedule_shows_the_synced_timetable(): void
    {
        $this->scheduleSession();
        app(ClassScheduleSync::class)->sync($this->scheduleId);

        $schedule = app(StudentDashboardService::class)->weeklySchedule($this->studentUser->fresh());
        $this->assertTrue($schedule['has_any']);
        $this->assertSame('Science', $schedule['days']['Monday'][0]['subject']);

        $this->actingAs($this->studentUser)
            ->get(route('student.schedule.index'))
            ->assertOk()
            ->assertSee('Science')
            ->assertDontSee('No classes are scheduled for you yet.');
    }

    public function test_backfill_command_syncs_active_schedules(): void
    {
        $this->scheduleSession();

        $this->artisan('schedule:sync-class-schedules')
            ->assertSuccessful();

        $this->assertDatabaseHas('class_schedules', ['class_id' => $this->classId, 'day_of_week' => 'Monday']);
    }
}
