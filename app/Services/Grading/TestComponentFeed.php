<?php

namespace App\Services\Grading;

use App\Models\ClassModel;
use App\Models\Test;

/**
 * Rolls test scores into the gradebook automatically — the test-side twin of
 * {@see HomeworkComponentFeed}. When a test is tagged with a grade component,
 * syncing it recomputes that component for the class through
 * {@see ComponentScoreRecomputer}, which folds this test's scores in with every
 * other source (other tests, homework, hand-entered activities) as
 * Σ raw ÷ Σ max × 100 and writes component_scores — the table the gradebook reads.
 *
 * Both delivery modes feed the same grade from their own source: F2F from
 * omr_results (per sheet), online from test_attempts (the student's latest
 * submitted attempt per test). Online attempts count while still provisional
 * (essays pending), so the score re-fires and finalises when they are graded.
 */
class TestComponentFeed
{
    public function __construct(private ComponentScoreRecomputer $recomputer) {}

    public function sync(Test $test): void
    {
        if (! $test->grade_component_id) {
            return; // untagged test does not feed the gradebook
        }

        $class = $test->class_id ? ClassModel::find($test->class_id) : null;
        if (! $class) {
            return; // a personal test belongs to no class, so it has no gradebook
        }

        $this->recomputer->recompute(
            $class,
            (int) $test->grade_component_id,
            $test->grading_period ? (int) $test->grading_period : null,
        );
    }
}
