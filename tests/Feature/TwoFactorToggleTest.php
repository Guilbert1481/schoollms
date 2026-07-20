<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Support\SecurityNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Profile Security-tab 2FA toggle + bell nudge.
 *
 * Enabling runs through the existing /2fa/setup flow; this covers the new
 * self-service DISABLE endpoint (re-auth required, mandatory roles blocked)
 * and the virtual "enable 2FA" notification.
 */
class TwoFactorToggleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
    }

    private function enrolled(string $role = 'student'): User
    {
        return User::factory()->create([
            'school_id' => $this->school->id,
            'role' => $role,
            'google2fa_secret' => 'SECRETSECRET1234',
            'recovery_codes' => encrypt(json_encode(['AAAA', 'BBBB'])),
        ]);
    }

    public function test_optional_role_can_disable_with_correct_password(): void
    {
        $user = $this->enrolled('student');

        $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->post(route('settings.profile.2fa.disable'), ['current_password' => 'password'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertNull($user->google2fa_secret);
        $this->assertNull($user->recovery_codes);
    }

    public function test_disable_requires_the_correct_password(): void
    {
        $user = $this->enrolled('student');

        $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->post(route('settings.profile.2fa.disable'), ['current_password' => 'wrong-password'])
            ->assertSessionHasErrors('current_password');

        $this->assertNotNull($user->fresh()->google2fa_secret, '2FA must stay on when the password is wrong.');
    }

    public function test_mandatory_role_cannot_disable(): void
    {
        config(['security.enforce_2fa' => true]); // phpunit disables it globally

        $user = $this->enrolled('admin');

        $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->post(route('settings.profile.2fa.disable'), ['current_password' => 'password'])
            ->assertSessionHasErrors('two_factor');

        $this->assertNotNull($user->fresh()->google2fa_secret, 'Mandatory-role 2FA must not be removable.');
    }

    public function test_security_tab_renders_enable_cta_when_off(): void
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);

        $this->actingAs($user)
            ->get(route('settings.profile'))
            ->assertOk()
            ->assertSee('Two-Factor Authentication')
            ->assertSee('Enable Two-Factor Authentication');
    }

    public function test_security_tab_renders_disable_form_when_on(): void
    {
        $user = $this->enrolled('student');

        $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->get(route('settings.profile'))
            ->assertOk()
            ->assertSee('Turn Off Two-Factor Authentication');
    }

    public function test_nudge_shows_only_until_two_factor_is_enabled(): void
    {
        $without = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $rows = SecurityNotifications::forUser($without);
        $this->assertCount(1, $rows);
        $this->assertSame('security_2fa', $rows[0]['type']);
        $this->assertSame('enable-2fa', $rows[0]['id']);

        $this->assertSame([], SecurityNotifications::forUser($this->enrolled('student')));
    }
}
