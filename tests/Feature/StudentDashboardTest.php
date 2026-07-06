<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The student dashboard renders registry-driven KPIs and live widget data
 * (subjects, deadlines, billing, announcements) for the signed-in student,
 * gracefully handles students with no enrollment yet, and the master
 * /dashboard route hands students off to it.
 */
class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->user   = User::factory()->create([
            'school_id' => $this->school->id,
            'role'      => 'student',
        ]);
    }

    /** Insert a row, auto-filling NOT NULL columns that have no default. */
    private function insertWithDefaults(string $table, array $values): int
    {
        $cols = DB::select(
            'select column_name, data_type, is_nullable, column_default, extra
             from information_schema.columns
             where table_schema = database() and table_name = ?',
            [$table]
        );
        foreach ($cols as $c) {
            $c = array_change_key_case((array) $c, CASE_LOWER);
            if (array_key_exists($c['column_name'], $values)
                || strtoupper((string) $c['is_nullable']) === 'YES'
                || $c['column_default'] !== null
                || str_contains(strtolower((string) $c['extra']), 'auto_increment')) {
                continue;
            }
            $values[$c['column_name']] = match (true) {
                in_array(strtolower((string) $c['data_type']), ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'double', 'float']) => 0,
                strtolower((string) $c['data_type']) === 'date' => date('Y-m-d'),
                in_array(strtolower((string) $c['data_type']), ['datetime', 'timestamp']) => date('Y-m-d H:i:s'),
                strtolower((string) $c['data_type']) === 'json' => '[]',
                default => '',
            };
        }

        return (int) DB::table($table)->insertGetId($values);
    }

    /** Seed a full academic context: enrollment + two subjects, tasks, invoice, announcement. */
    private function seedAcademicWorld(): void
    {
        $studentId = $this->insertWithDefaults('students', [
            'school_id' => $this->school->id, 'user_id' => $this->user->id,
            'first_name' => 'Dash', 'last_name' => 'Board',
            'student_number' => 'DASH-001', 'status' => 'active',
        ]);
        $nodeId = $this->insertWithDefaults('education_nodes', [
            'name' => 'Basic Education', 'node_type' => 'level',
            'is_offered' => 1, 'is_active' => 1, 'order_index' => 0,
        ]);
        $ayId = $this->insertWithDefaults('academic_years', [
            'school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => 1,
        ]);
        $termId = $this->insertWithDefaults('terms', [
            'school_id' => $this->school->id, 'academic_year_id' => $ayId,
            'name' => '1st Sem', 'status' => 'active',
        ]);
        $enrollmentId = $this->insertWithDefaults('student_enrollments', [
            'school_id' => $this->school->id, 'student_id' => $studentId,
            'academic_year_id' => $ayId, 'term_id' => $termId,
            'education_node_id' => $nodeId, 'year_level' => 7,
            'status' => 'enrolled',
        ]);

        $mathId = $this->insertWithDefaults('subjects', [
            'school_id' => $this->school->id, 'code' => 'MATH7', 'name' => 'Mathematics 7',
            'units' => 3, 'is_active' => 1,
        ]);
        $engId = $this->insertWithDefaults('subjects', [
            'school_id' => $this->school->id, 'code' => 'ENG7', 'name' => 'English 7',
            'units' => 3, 'is_active' => 1,
        ]);
        $this->insertWithDefaults('student_enrollment_subjects', [
            'student_enrollment_id' => $enrollmentId, 'subject_id' => $mathId,
            'status' => 'enrolled', 'grade' => 92, 'progress_percentage' => 80,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->insertWithDefaults('student_enrollment_subjects', [
            'student_enrollment_id' => $enrollmentId, 'subject_id' => $engId,
            'status' => 'enrolled', 'progress_percentage' => 40,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // One pending (due tomorrow), one overdue, one completed task.
        foreach ([
            ['title' => 'Algebra worksheet', 'due' => now()->addDay(),  'status' => 'pending', 'done' => 0],
            ['title' => 'Essay draft',       'due' => now()->subDays(2), 'status' => 'overdue', 'done' => 0],
            ['title' => 'Reading log',       'due' => now()->subWeek(),  'status' => 'completed', 'done' => 1],
        ] as $t) {
            $deadlineId = $this->insertWithDefaults('deadlines', [
                'school_id' => $this->school->id, 'created_by' => $this->user->id,
                'title' => $t['title'], 'type' => 'assignment',
                'due_date' => $t['due']->format('Y-m-d H:i:s'), 'active' => 1,
            ]);
            $this->insertWithDefaults('deadline_user_completions', [
                'deadline_id' => $deadlineId, 'user_id' => $this->user->id,
                'status' => $t['status'], 'is_completed' => $t['done'],
            ]);
        }

        Invoice::create([
            'invoice_number' => 'INV-DASH-1',
            'school_id'      => $this->school->id,
            'student_id'     => $this->user->id,
            'total_amount'   => 1500,
            'paid_amount'    => 0,
            'balance'        => 1500,
            'status'         => Invoice::STATUS_UNPAID,
            'billing_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(10)->toDateString(),
        ]);

        $this->insertWithDefaults('announcements', [
            'school_id' => $this->school->id, 'created_by' => $this->user->id,
            'title' => 'Intramurals registration open', 'content' => 'Sign up at the gym.',
            'published_at' => now()->subHour()->format('Y-m-d H:i:s'), 'is_active' => 1,
            'priority_level' => 'normal',
        ]);
    }

    public function test_dashboard_renders_live_kpis_and_widgets(): void
    {
        $this->seedAcademicWorld();

        $response = $this->actingAs($this->user)->get(route('student.dashboard'));

        $response->assertOk()
            // Registry-driven KPI cards
            ->assertSee('Active Subjects')
            ->assertSee('Current GWA')
            ->assertSee('92.00')            // weighted GWA from the posted grade
            ->assertSee('Outstanding Balance')
            ->assertSee('₱1,500.00')
            ->assertSee('Enrolled Units')
            // Widgets
            ->assertSee('Mathematics 7')
            ->assertSee('Algebra worksheet')
            ->assertSee('Intramurals registration open')
            ->assertSee('Study Coach')
            ->assertSee('Upcoming Deadlines');
    }

    public function test_master_dashboard_route_redirects_students_here(): void
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_student_without_enrollment_still_gets_a_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Active Subjects')
            ->assertSee('No enrolled subjects this term.');
    }
}
