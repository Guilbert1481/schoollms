<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap M2 — two-factor authentication: mandatory enrolment for staff
 * roles, the once-per-session challenge for anyone enrolled, and recovery
 * keys. The suite runs with enforcement off (phpunit.xml); these tests turn
 * it on explicitly.
 */
class TwoFactorEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        config(['security.enforce_2fa' => true]);
    }

    private function staff(string $role = 'finance_manager'): User
    {
        return User::factory()->create(['school_id' => $this->school->id, 'role' => $role]);
    }

    public function test_staff_without_authenticator_is_locked_to_setup_after_login(): void
    {
        $staff = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'finance_manager',
            'email' => 'money@example.com',
        ]);

        $this->post('/login', ['email' => 'money@example.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertRedirect(route('2fa.setup'));

        // Deep links are gated too — not just the dashboard.
        $this->get(route('finance.payments.index'))->assertRedirect(route('2fa.setup'));

        // The escape hatches stay reachable.
        $this->get(route('2fa.setup'))->assertOk();
    }

    public function test_student_is_never_forced_to_enrol(): void
    {
        $student = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $this->assertFalse(
            str_contains((string) $response->headers->get('Location'), '/2fa/'),
            'A student without 2FA must not be redirected into the 2FA flow.'
        );
    }

    public function test_full_enrolment_flow_activates_the_shield_and_issues_recovery_codes(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('2fa.setup'))->assertOk()->assertSee('Activate Security Shield');

        $secret = session('2fa_setup_secret');
        $this->assertNotEmpty($secret);

        $otp = app('pragmarx.google2fa')->getCurrentOtp($secret);

        $this->post(route('2fa.setup.confirm'), ['one_time_password' => $otp])
            ->assertOk()
            ->assertSee('Save Your Recovery Keys');

        $staff->refresh();
        $this->assertSame($secret, $staff->google2fa_secret);
        $this->assertNotEmpty($staff->recovery_codes);
        $this->assertCount(8, json_decode(decrypt($staff->recovery_codes), true));

        // Enrolled + verified in this session → no more 2FA redirects.
        $location = (string) $this->get(route('dashboard'))->headers->get('Location');
        $this->assertFalse(str_contains($location, '/2fa/'));
    }

    public function test_wrong_code_does_not_enrol(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('2fa.setup'))->assertOk();

        $this->post(route('2fa.setup.confirm'), ['one_time_password' => '000000'])
            ->assertSessionHasErrors('otp');

        $this->assertNull($staff->fresh()->google2fa_secret);
    }

    public function test_enrolled_user_is_challenged_once_per_session(): void
    {
        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();

        $staff = $this->staff('registrar');
        $staff->forceFill(['google2fa_secret' => $secret])->save();

        // Unverified session → bounced to the challenge, even on deep links.
        $this->actingAs($staff)->get(route('dashboard'))->assertRedirect(route('2fa.verify'));
        $this->get(route('2fa.verify'))->assertOk()->assertSee('Identity Challenge');

        // Wrong code stays on the challenge.
        $this->post(route('2fa.verify.post'), ['one_time_password' => '000000'])
            ->assertSessionHasErrors('otp');

        // Right code unlocks the session.
        $this->post(route('2fa.verify.post'), ['one_time_password' => $google2fa->getCurrentOtp($secret)])
            ->assertRedirect(route('dashboard'));

        $location = (string) $this->get(route('dashboard'))->headers->get('Location');
        $this->assertFalse(str_contains($location, '/2fa/'));
    }

    public function test_recovery_key_is_single_use(): void
    {
        $google2fa = app('pragmarx.google2fa');

        $staff = $this->staff('admin');
        $staff->forceFill([
            'google2fa_secret' => $google2fa->generateSecretKey(),
            'recovery_codes' => encrypt(json_encode(['AAAA111111', 'BBBB222222'])),
        ])->save();

        $this->actingAs($staff)->get(route('2fa.recovery'))->assertOk();

        $this->post(route('2fa.recovery.post'), ['recovery_key' => 'aaaa111111'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(['BBBB222222'], json_decode(decrypt($staff->fresh()->recovery_codes), true));

        // A fresh session cannot reuse the burned key.
        $this->app['session']->flush();
        $this->actingAs($staff->fresh());
        $this->post(route('2fa.recovery.post'), ['recovery_key' => 'AAAA111111'])
            ->assertSessionHasErrors('key');
    }
}
