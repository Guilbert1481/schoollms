<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap D4 — privileged roles get a shorter idle timeout than the global
 * session lifetime. After security.staff_idle_timeout minutes with no request,
 * a staff session is invalidated server-side; non-staff roles are untouched.
 */
class StaffSessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        config(['security.staff_idle_timeout' => 30]);
    }

    private function staff(string $role = 'finance_manager'): User
    {
        return User::factory()->create(['school_id' => $this->school->id, 'role' => $role]);
    }

    public function test_active_staff_session_is_not_timed_out(): void
    {
        $user = $this->staff();
        $this->actingAs($user);

        // Staff dashboards render a view directly (200), not a redirect.
        $this->get('/dashboard')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_idle_staff_session_is_evicted(): void
    {
        $user = $this->staff();
        $this->actingAs($user);

        // Simulate the last request having happened beyond the idle window.
        $stale = now()->subMinutes(31)->getTimestamp();

        $this->withSession(['staff_last_activity_at' => $stale])
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_non_staff_role_is_never_timed_out(): void
    {
        $student = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $this->actingAs($student);

        $stale = now()->subMinutes(120)->getTimestamp();

        $this->withSession(['staff_last_activity_at' => $stale])
            ->get('/dashboard')
            ->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticatedAs($student);
    }

    public function test_zero_timeout_disables_the_check(): void
    {
        config(['security.staff_idle_timeout' => 0]);

        $user = $this->staff();
        $this->actingAs($user);

        $stale = now()->subMinutes(600)->getTimestamp();

        $this->withSession(['staff_last_activity_at' => $stale])
            ->get('/dashboard')
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }
}
