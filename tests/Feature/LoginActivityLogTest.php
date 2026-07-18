<?php

namespace Tests\Feature;

use App\Listeners\LogAuthenticationActivity;
use App\Models\AuditLog;
use App\Models\GradingSystem;
use App\Models\LoginLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Roadmap Phase 4 — login success/failure logging, the failed-auth threshold
 * alert, the superadmin Logins viewer, and the admin-action audit extension.
 */
class LoginActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->user = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'email' => 'learner@example.com',
        ]);
    }

    public function test_successful_login_writes_a_success_row(): void
    {
        $this->post('/login', ['email' => 'learner@example.com', 'password' => 'password'])
            ->assertRedirect();

        // Exactly one row — guards against the listener being registered
        // twice (manual Event::listen + auto-discovery).
        $this->assertSame(1, LoginLog::where('event', LoginLog::EVENT_SUCCESS)->count());

        $log = LoginLog::where('event', LoginLog::EVENT_SUCCESS)->firstOrFail();
        $this->assertSame('learner@example.com', $log->email);
        $this->assertSame((int) $this->user->id, (int) $log->user_id);
        $this->assertSame((int) $this->school->id, (int) $log->school_id);
        $this->assertNotEmpty($log->ip);
    }

    public function test_failed_login_writes_a_failed_row(): void
    {
        $this->post('/login', ['email' => 'learner@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, LoginLog::where('event', LoginLog::EVENT_FAILED)->count());

        $log = LoginLog::where('event', LoginLog::EVENT_FAILED)->firstOrFail();
        $this->assertSame('learner@example.com', $log->email);
        $this->assertSame((int) $this->user->id, (int) $log->user_id);
        $this->assertSame(0, LoginLog::where('event', LoginLog::EVENT_SUCCESS)->count());
    }

    public function test_failed_auth_threshold_raises_a_log_warning(): void
    {
        // Seed just under the threshold from the same IP the test client uses,
        // then let one real failed attempt cross it via the listener.
        for ($i = 1; $i < LogAuthenticationActivity::ALERT_THRESHOLD; $i++) {
            LoginLog::create([
                'email' => "guess{$i}@example.com",
                'event' => LoginLog::EVENT_FAILED,
                'ip' => '127.0.0.1',
            ]);
        }

        Log::spy();

        $this->post('/login', ['email' => 'learner@example.com', 'password' => 'wrong-password']);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message) => $message === 'auth.failed.threshold')
            ->once();
    }

    public function test_superadmin_sees_the_logins_page_and_others_do_not(): void
    {
        LoginLog::create([
            'email' => 'learner@example.com',
            'user_id' => $this->user->id,
            'school_id' => $this->school->id,
            'event' => LoginLog::EVENT_FAILED,
            'ip' => '10.0.0.9',
        ]);

        $superadmin = User::factory()->create(['school_id' => null, 'role' => 'superadmin']);

        $this->actingAs($superadmin)
            ->get(route('superadmin.logs.logins'))
            ->assertOk()
            ->assertSee('learner@example.com')
            ->assertSee('10.0.0.9');

        // Outcome filter narrows the list.
        $this->actingAs($superadmin)
            ->get(route('superadmin.logs.logins', ['event' => 'success']))
            ->assertOk()
            ->assertDontSee('10.0.0.9');

        // A school admin has no business on the platform login log.
        $admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('superadmin.logs.logins'))
            ->assertForbidden();
    }

    public function test_admin_config_change_is_audited(): void
    {
        $this->actingAs($this->user);

        $system = GradingSystem::create(['name' => 'Default', 'type' => 'percentage', 'passing_mark' => 75]);
        $system->update(['passing_mark' => 80]);

        $audit = AuditLog::where('auditable_type', GradingSystem::class)
            ->where('auditable_id', $system->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertEquals(75, (float) $audit->before['passing_mark']);
        $this->assertEquals(80, (float) $audit->after['passing_mark']);
    }

    public function test_config_secrets_never_reach_the_audit_log(): void
    {
        $this->actingAs($this->user);

        $setting = \App\Models\FinanceSetting::forSchool($this->school->id);
        $setting->update(['smtp_password' => 'super-secret-smtp', 'invoice_due_days' => 10]);

        $audit = AuditLog::where('auditable_type', \App\Models\FinanceSetting::class)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertEquals(10, (int) $audit->after['invoice_due_days']);
        $this->assertArrayNotHasKey('smtp_password', (array) $audit->after);
        $this->assertStringNotContainsString(
            'super-secret-smtp',
            json_encode(AuditLog::where('auditable_type', \App\Models\FinanceSetting::class)->get()->toArray())
        );
    }
}
