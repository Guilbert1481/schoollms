<?php

namespace App\Services\Tests;

use App\Models\Test;
use Illuminate\Support\Carbon;

/**
 * Answers "can this online test be taken right now, and until when?" — the single
 * source of truth for the student's Assessments list, the bell, and the server-side
 * guards on start/answer/submit.
 *
 * Two availability shapes (set on test_settings by the builder, mutually exclusive):
 *  - duration: a timer, no calendar window — open the moment it is published.
 *  - schedule: a start/end window — open only between them.
 *
 * Only mode = online tests are ever "takeable"; F2F tests are printed and scanned,
 * so they never surface here.
 */
class TestAvailability
{
    public const NOT_PUBLISHED = 'not_published';

    public const UPCOMING = 'upcoming';

    public const OPEN = 'open';

    public const CLOSED = 'closed';

    public function isOnline(Test $test): bool
    {
        return ($test->settings->mode ?? null) === 'online';
    }

    public function isPublished(Test $test): bool
    {
        // A test is released to students when the teacher publishes it in Test
        // Management (tests.status = 'published'). The legacy test_availabilities
        // .is_published flag is never actually set, so this is the real signal.
        return $test->status === 'published';
    }

    /** When the window opens: start_at for schedule; null (open-on-publish) for duration. */
    public function opensAt(Test $test): ?Carbon
    {
        $s = $test->settings;
        if ($s && $s->availability_mode === 'schedule' && $s->start_at) {
            return Carbon::parse($s->start_at);
        }

        return null;
    }

    /** When the window closes: end_at for schedule; null (no wall-clock close) for duration. */
    public function closesAt(Test $test): ?Carbon
    {
        $s = $test->settings;
        if ($s && $s->availability_mode === 'schedule' && $s->end_at) {
            return Carbon::parse($s->end_at);
        }

        return null;
    }

    public function status(Test $test, ?Carbon $now = null): string
    {
        if (! $this->isPublished($test)) {
            return self::NOT_PUBLISHED;
        }

        $now = $now ?: now();
        $opens = $this->opensAt($test);
        $closes = $this->closesAt($test);

        if ($opens && $now->lt($opens)) {
            return self::UPCOMING;
        }
        if ($closes && $now->gt($closes)) {
            return self::CLOSED;
        }

        return self::OPEN;
    }

    public function isOpen(Test $test, ?Carbon $now = null): bool
    {
        return $this->status($test, $now) === self::OPEN;
    }

    /**
     * Server-authoritative end of a sitting started at $startedAt. Duration adds the
     * timer to the start; schedule uses the window's close; if both a per-attempt
     * timer and a window apply, the EARLIER wins (you get your minutes, but never
     * past when the window shuts). Null means untimed and open-ended.
     */
    public function deadlineFor(Test $test, Carbon $startedAt): ?Carbon
    {
        $s = $test->settings;
        $ends = [];

        $minutes = $s->duration_minutes ?? $s->timer_minutes ?? null;
        if ($minutes) {
            $ends[] = $startedAt->copy()->addMinutes((int) $minutes);
        }
        if ($closes = $this->closesAt($test)) {
            $ends[] = $closes;
        }

        $deadline = null;
        foreach ($ends as $e) {
            if ($deadline === null || $e->lt($deadline)) {
                $deadline = $e;
            }
        }

        return $deadline;
    }

    public function attemptsAllowed(Test $test): int
    {
        return max(1, (int) ($test->settings->attempts_allowed ?? 1));
    }
}
