<?php

namespace App\Services\Grading;

use App\Models\ClassModel;
use App\Models\Homework;

/**
 * Rolls graded homework into the gradebook automatically. When a homework is
 * tagged with a grade component, syncing it recomputes that component for the
 * class through {@see ComponentScoreRecomputer}, which folds this homework in
 * with every other source (other homework, tests, hand-entered activities) as
 * Σ earned ÷ Σ possible × 100 and writes component_scores — the table the
 * gradebook reads — so the computed grade includes homework with no double entry.
 */
class HomeworkComponentFeed
{
    public function __construct(private ComponentScoreRecomputer $recomputer) {}

    public function sync(Homework $homework): void
    {
        if (! $homework->grade_component_id) {
            return; // untagged homework does not feed the gradebook
        }

        $class = ClassModel::find($homework->class_id);
        if (! $class) {
            return;
        }

        $this->recomputer->recompute(
            $class,
            (int) $homework->grade_component_id,
            $homework->grading_period ? (int) $homework->grading_period : null,
        );
    }
}
