<?php

namespace Tests\Feature;

use App\Services\Attendance\QrAttendanceTokenService;
use Tests\TestCase;

/**
 * Item #3 — the rotating QR token. A token is valid only for its window (plus
 * the previous one for the rotation boundary), and is bound to its context so a
 * code for one class can't mark attendance in another.
 */
class QrAttendanceTokenTest extends TestCase
{
    private QrAttendanceTokenService $tokens;

    /** A window-aligned timestamp so slot math is exact. */
    private const AT = 1_800_000_000;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokens = new QrAttendanceTokenService;
    }

    public function test_a_fresh_token_verifies(): void
    {
        $token = $this->tokens->token('session:5', 10, self::AT);
        $this->assertTrue($this->tokens->verify('session:5', $token, 10, self::AT));
    }

    public function test_the_previous_window_is_still_accepted(): void
    {
        $token = $this->tokens->token('session:5', 10, self::AT);
        // One window later — the rotation boundary — is still valid.
        $this->assertTrue($this->tokens->verify('session:5', $token, 10, self::AT + 10));
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $token = $this->tokens->token('session:5', 10, self::AT);
        // Two windows later → no longer valid.
        $this->assertFalse($this->tokens->verify('session:5', $token, 10, self::AT + 20));
    }

    public function test_a_token_for_another_context_is_rejected(): void
    {
        $token = $this->tokens->token('session:5', 10, self::AT);
        $this->assertFalse($this->tokens->verify('session:9', $token, 10, self::AT));
        $this->assertFalse($this->tokens->verify('daily:5', $token, 10, self::AT));
    }
}
