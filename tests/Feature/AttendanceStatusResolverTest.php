<?php

namespace Tests\Feature;

use App\Services\Attendance\AttendanceStatusResolver;
use Tests\TestCase;

/**
 * Item #2 — deriving present/late/absent from a captured time-in and the level's
 * late rule. Used by device/QR capture (which records a time, not a decision).
 */
class AttendanceStatusResolverTest extends TestCase
{
    private AttendanceStatusResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new AttendanceStatusResolver;
    }

    public function test_no_time_in_is_absent(): void
    {
        $this->assertSame('absent', $this->resolver->derive(null));
        $this->assertSame('absent', $this->resolver->derive(''));
    }

    public function test_time_in_without_a_cutoff_is_present(): void
    {
        $this->assertSame('present', $this->resolver->derive('08:15'));
    }

    public function test_arriving_after_the_cutoff_is_late(): void
    {
        // Cutoff 08:00 + 5 grace = 08:05; arrived 08:15 → late.
        $this->assertSame('late', $this->resolver->derive('08:15', '08:00', 5));
    }

    public function test_arriving_within_the_grace_window_is_present(): void
    {
        // Cutoff 08:00 + 20 grace = 08:20; arrived 08:15 → present.
        $this->assertSame('present', $this->resolver->derive('08:15', '08:00', 20));
    }

    public function test_arriving_exactly_at_the_cutoff_is_present(): void
    {
        // On-the-dot is not late (strictly-after is late).
        $this->assertSame('present', $this->resolver->derive('08:00', '08:00', 0));
    }
}
