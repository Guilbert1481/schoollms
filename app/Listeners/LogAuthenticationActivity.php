<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Support\AuditTrail;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * Roadmap Phase 4 — records every login success/failure into login_logs and
 * raises the failed-auth threshold alert. Purely additive: it never changes
 * the outcome of the attempt.
 *
 * Note: LoginController's superadmin/parent fallbacks run a second
 * Auth::attempt after a school-scoped miss, so a successful fallback login
 * legitimately leaves one `failed` row followed by a `success` row.
 */
class LogAuthenticationActivity
{
    /** Failures from one IP or for one email inside the window that trigger the alert. */
    public const ALERT_THRESHOLD = 10;

    public const ALERT_WINDOW_MINUTES = 15;

    public function handleLogin(Login $event): void
    {
        $this->record(LoginLog::EVENT_SUCCESS, $event->user->email, $event->user);
    }

    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? ($event->user->email ?? '');

        $this->record(LoginLog::EVENT_FAILED, (string) $email, $event->user);

        $this->alertOnThreshold((string) $email);
    }

    private function record(string $outcome, string $email, $user): void
    {
        $ip = request()?->ip();
        $userAgent = substr((string) request()?->userAgent(), 0, 255);

        LoginLog::create([
            'email' => $email,
            'user_id' => $user?->id,
            'school_id' => $user?->school_id,
            'event' => $outcome,
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);

        // S4 — mirror to the off-database trail. Auth events fire outside any
        // DB transaction, so this records immediately.
        AuditTrail::record('auth', [
            'email' => $email,
            'user_id' => $user?->id,
            'school_id' => $user?->school_id,
            'event' => $outcome,
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Failed-auth threshold alert: when one IP or one email accumulates
     * ALERT_THRESHOLD failures inside the window, write a searchable
     * warning. Fires on every attempt past the threshold — the login
     * throttle (Phase 0) is what actually slows the attacker down; this
     * makes the pattern visible.
     */
    private function alertOnThreshold(string $email): void
    {
        $since = now()->subMinutes(self::ALERT_WINDOW_MINUTES);
        $ip = request()?->ip();

        $byIp = $ip
            ? LoginLog::where('event', LoginLog::EVENT_FAILED)->where('ip', $ip)->where('created_at', '>=', $since)->count()
            : 0;
        $byEmail = $email !== ''
            ? LoginLog::where('event', LoginLog::EVENT_FAILED)->where('email', $email)->where('created_at', '>=', $since)->count()
            : 0;

        if (max($byIp, $byEmail) >= self::ALERT_THRESHOLD) {
            Log::warning('auth.failed.threshold', [
                'email' => $email,
                'ip' => $ip,
                'failures_by_ip' => $byIp,
                'failures_by_email' => $byEmail,
                'window_minutes' => self::ALERT_WINDOW_MINUTES,
            ]);
        }
    }
}
