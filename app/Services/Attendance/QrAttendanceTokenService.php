<?php

namespace App\Services\Attendance;

/**
 * Stateless rotating token for QR attendance. The teacher's live screen shows a
 * QR that changes every N seconds; a scanned code is only valid for its time
 * window, so a screenshot forwarded to an absent classmate expires almost
 * immediately.
 *
 * The token is an HMAC over the capture context and the time window, keyed by
 * the app key — so nothing is stored, and a tampered context (different class)
 * fails to verify. Verification accepts the current and the previous window to
 * tolerate the rotation boundary and small clock skew.
 */
class QrAttendanceTokenService
{
    /** Rotation interval used by the live screen and the scan check. */
    public const DEFAULT_WINDOW = 10;

    /** Current token for a context, for a given rotation interval. */
    public function token(string $context, int $windowSeconds, ?int $at = null): string
    {
        return $this->sign($context, $this->slot($windowSeconds, $at));
    }

    /** True if $token is valid for $context right now (current or previous window). */
    public function verify(string $context, string $token, int $windowSeconds, ?int $at = null): bool
    {
        $slot = $this->slot($windowSeconds, $at);

        return hash_equals($this->sign($context, $slot), $token)
            || hash_equals($this->sign($context, $slot - 1), $token);
    }

    private function slot(int $windowSeconds, ?int $at): int
    {
        $windowSeconds = max(3, $windowSeconds);
        $at ??= now()->timestamp;

        return intdiv($at, $windowSeconds);
    }

    private function sign(string $context, int $slot): string
    {
        return substr(hash_hmac('sha256', "att:{$context}:{$slot}", (string) config('app.key')), 0, 32);
    }
}
