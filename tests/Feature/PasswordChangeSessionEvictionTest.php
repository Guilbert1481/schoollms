<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Roadmap M6 — password changes evict other active sessions.
 *
 * AuthenticateSession (bootstrap/app.php web group) stores the user's password
 * hash in the session and logs the session out when the account's hash no
 * longer matches — so a password reset actually cuts off an attacker holding a
 * stolen session, instead of leaving it alive until expiry.
 */
class PasswordChangeSessionEvictionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->student = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'password' => Hash::make('original-password'),
        ]);
    }

    public function test_session_survives_while_password_is_unchanged(): void
    {
        $this->actingAs($this->student);

        // The master /dashboard route redirects each role to its own dashboard;
        // what matters here is that the session stays authenticated across requests.
        $this->get('/dashboard')->assertRedirect(route('student.dashboard'));
        $this->get('/dashboard')->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($this->student);
    }

    public function test_password_change_logs_out_other_sessions(): void
    {
        $this->actingAs($this->student);

        // First request stores the current password hash in this session.
        $this->get('/dashboard')->assertRedirect(route('student.dashboard'));

        // The password changes elsewhere (reset flow, admin reset, or an
        // attacker-victim recovering their account from another device).
        $this->student->forceFill(['password' => Hash::make('brand-new-password')])->save();

        // This (now stale) session is evicted on its next request.
        $this->get('/dashboard')->assertRedirect();
        $this->assertGuest();
    }
}
