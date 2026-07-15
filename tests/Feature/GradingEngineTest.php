<?php

namespace Tests\Feature;

use App\Services\Grading\GradingEngine;
use Tests\TestCase;

/**
 * Phase 3a — the pure grade computation. Grade-critical, so every branch is
 * pinned: normalisation when weights don't total 100, the attendance
 * contribution, completeness, pass/fail boundaries, and the empty case.
 */
class GradingEngineTest extends TestCase
{
    private GradingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new GradingEngine;
    }

    public function test_weighted_average_normalises_when_weights_do_not_total_100(): void
    {
        // WW 30 / PT 40 / QA 20 = 90 total; scores 90 / 80 / 70.
        // (90*30 + 80*40 + 70*20) / 90 = 7300 / 90 = 81.11
        $r = $this->engine->compute(
            componentWeights: [1 => 30, 2 => 40, 3 => 20],
            scores: [1 => 90, 2 => 80, 3 => 70],
            passingMark: 75,
        );

        $this->assertEqualsWithDelta(81.11, $r->final, 0.001);
        $this->assertTrue($r->passed);
        $this->assertTrue($r->isComplete);
        $this->assertSame(90.0, $r->weightUsed);
    }

    public function test_attendance_contributes_when_weighted(): void
    {
        // Same 7300 component sum, plus attendance 10 at 100% → (7300 + 1000) / 100 = 83.
        $r = $this->engine->compute(
            componentWeights: [1 => 30, 2 => 40, 3 => 20],
            scores: [1 => 90, 2 => 80, 3 => 70],
            passingMark: 75,
            attendanceWeight: 10,
            attendanceRate: 100,
        );

        $this->assertEqualsWithDelta(83.0, $r->final, 0.001);
        $this->assertTrue($r->isComplete);
        $this->assertSame(100.0, $r->weightUsed);
    }

    public function test_low_attendance_drags_the_grade_down(): void
    {
        // Attendance 10 at 50% → (7300 + 500) / 100 = 78.
        $r = $this->engine->compute(
            componentWeights: [1 => 30, 2 => 40, 3 => 20],
            scores: [1 => 90, 2 => 80, 3 => 70],
            passingMark: 75,
            attendanceWeight: 10,
            attendanceRate: 50,
        );

        $this->assertEqualsWithDelta(78.0, $r->final, 0.001);
    }

    public function test_pass_mark_is_inclusive(): void
    {
        $r = $this->engine->compute([1 => 100], [1 => 75], passingMark: 75);

        $this->assertEqualsWithDelta(75.0, $r->final, 0.001);
        $this->assertTrue($r->passed);
    }

    public function test_below_pass_mark_fails(): void
    {
        $r = $this->engine->compute([1 => 100], [1 => 74.99], passingMark: 75);

        $this->assertFalse($r->passed);
    }

    public function test_a_missing_component_score_marks_incomplete_and_is_excluded(): void
    {
        // PT (40) unscored → averaged over WW(30)+QA(20): (90*30 + 70*20) / 50 = 82.
        $r = $this->engine->compute(
            componentWeights: [1 => 30, 2 => 40, 3 => 20],
            scores: [1 => 90, 2 => null, 3 => 70],
            passingMark: 75,
        );

        $this->assertEqualsWithDelta(82.0, $r->final, 0.001);
        $this->assertFalse($r->isComplete);
        $this->assertSame(50.0, $r->weightUsed);
    }

    public function test_weighted_attendance_without_a_rate_is_incomplete(): void
    {
        // Attendance is weighted but no rate known → grade over components only, incomplete.
        $r = $this->engine->compute(
            componentWeights: [1 => 30, 2 => 40, 3 => 20],
            scores: [1 => 90, 2 => 80, 3 => 70],
            passingMark: 75,
            attendanceWeight: 10,
            attendanceRate: null,
        );

        $this->assertEqualsWithDelta(81.11, $r->final, 0.001);
        $this->assertFalse($r->isComplete);
    }

    public function test_zero_weight_components_are_ignored(): void
    {
        // Component 2 has weight 0 → skipped entirely; its missing score is not
        // counted against completeness.
        $r = $this->engine->compute(
            componentWeights: [1 => 50, 2 => 0],
            scores: [1 => 80, 2 => null],
            passingMark: 75,
        );

        $this->assertEqualsWithDelta(80.0, $r->final, 0.001);
        $this->assertTrue($r->isComplete);
        $this->assertSame(50.0, $r->weightUsed);
    }

    public function test_no_inputs_yields_an_empty_result(): void
    {
        $r = $this->engine->compute([1 => 30], [1 => null], passingMark: 75);

        $this->assertNull($r->final);
        $this->assertNull($r->passed);
        $this->assertFalse($r->isComplete);
        $this->assertSame(0.0, $r->weightUsed);
    }
}
