<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestSetting;
use App\Models\User;
use App\Services\Tests\TestAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 1 of online testing — the availability resolver + attempt tables. This is
 * the gate every take-test action keys off, so it is pinned before any UI exists.
 */
class OnlineTestAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    }

    private function makeTest(array $settings = [], ?bool $published = true): Test
    {
        $test = Test::create([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'title' => 'Exam', 'status' => $published ? 'published' : 'draft',
        ]);
        TestSetting::create(array_merge([
            'test_id' => $test->id, 'mode' => 'online', 'availability_mode' => 'duration',
            'duration_minutes' => 30, 'attempts_allowed' => 1,
        ], $settings));

        return $test->fresh('settings');
    }

    private function resolver(): TestAvailability
    {
        return app(TestAvailability::class);
    }

    public function test_only_online_published_tests_are_takeable(): void
    {
        $online = $this->makeTest(['mode' => 'online']);
        $f2f = $this->makeTest(['mode' => 'f2f']);
        $unpublished = $this->makeTest(['mode' => 'online'], published: false);

        $this->assertTrue($this->resolver()->isOnline($online));
        $this->assertFalse($this->resolver()->isOnline($f2f));
        $this->assertTrue($this->resolver()->isOpen($online));
        $this->assertSame(TestAvailability::NOT_PUBLISHED, $this->resolver()->status($unpublished));
    }

    public function test_duration_test_is_open_the_moment_it_is_published(): void
    {
        $test = $this->makeTest(['availability_mode' => 'duration', 'duration_minutes' => 45]);

        $this->assertSame(TestAvailability::OPEN, $this->resolver()->status($test));
        $this->assertNull($this->resolver()->opensAt($test));
        $this->assertNull($this->resolver()->closesAt($test));
    }

    public function test_scheduled_test_moves_upcoming_then_open_then_closed(): void
    {
        $test = $this->makeTest([
            'availability_mode' => 'schedule', 'duration_minutes' => null,
            'start_at' => '2026-07-20 08:00:00', 'end_at' => '2026-07-20 10:00:00',
        ]);
        $r = $this->resolver();

        $this->assertSame(TestAvailability::UPCOMING, $r->status($test, Carbon::parse('2026-07-20 07:59:00')));
        $this->assertSame(TestAvailability::OPEN, $r->status($test, Carbon::parse('2026-07-20 09:00:00')));
        $this->assertSame(TestAvailability::CLOSED, $r->status($test, Carbon::parse('2026-07-20 10:01:00')));
    }

    public function test_deadline_duration_adds_the_timer_to_the_start(): void
    {
        $test = $this->makeTest(['availability_mode' => 'duration', 'duration_minutes' => 30]);
        $started = Carbon::parse('2026-07-20 09:00:00');

        $this->assertTrue($this->resolver()->deadlineFor($test, $started)->eq(Carbon::parse('2026-07-20 09:30:00')));
    }

    public function test_deadline_scheduled_uses_the_window_close(): void
    {
        $test = $this->makeTest([
            'availability_mode' => 'schedule', 'duration_minutes' => null,
            'start_at' => '2026-07-20 08:00:00', 'end_at' => '2026-07-20 10:00:00',
        ]);
        $started = Carbon::parse('2026-07-20 09:50:00');

        // 50 min into a 2h window with no per-attempt timer → deadline is the window close.
        $this->assertTrue($this->resolver()->deadlineFor($test, $started)->eq(Carbon::parse('2026-07-20 10:00:00')));
    }

    public function test_attempt_records_answers_and_detects_expiry(): void
    {
        $test = $this->makeTest();
        $student = Student::create(['school_id' => $this->school->id, 'student_number' => 'S-'.uniqid(),
            'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $levelId = DB::table('academic_levels')->insertGetId([
            'school_id' => $this->school->id, 'name' => 'Grade 5', 'sequence_order' => 5, 'type' => 'basic',
        ]);
        $subjectId = DB::table('subjects')->insertGetId([
            'school_id' => $this->school->id, 'code' => 'SCI5', 'name' => 'Science',
        ]);
        $topicId = DB::table('topics')->insertGetId([
            'school_id' => $this->school->id, 'subject_id' => $subjectId, 'name' => 'Matter',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $qid = DB::table('questions')->insertGetId([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'topic_id' => $topicId, 'academic_level_id' => $levelId, 'question_type' => 'multiple_choice',
            'question_text' => 'Q', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $attempt = TestAttempt::create([
            'school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $student->id,
            'status' => 'in_progress', 'started_at' => now()->subMinutes(31),
            'deadline_at' => now()->subMinute(),
        ]);
        $attempt->answers()->create([
            'question_id' => $qid, 'question_type' => 'multiple_choice',
            'response' => ['choice_id' => 5], 'points_possible' => 1,
        ]);

        $this->assertSame(1, $attempt->answers()->count());
        $this->assertTrue($attempt->answers->first()->isAnswered());
        $this->assertTrue($attempt->isExpired(), 'past its deadline while in progress → expired');
        $this->assertFalse($attempt->isSubmitted());
    }
}
