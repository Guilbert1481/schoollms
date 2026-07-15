<?php

namespace App\Services\Grading;

/**
 * Pure, deterministic grade computation — the correctness-critical heart of the
 * grading feature. Given a level's component weights + attendance weight and a
 * student's raw scores + attendance rate, it returns the weighted final.
 *
 * Decoupled from Eloquent on purpose: it takes primitives so it can be tested
 * exhaustively without a database. Callers extract the weights/scores from the
 * GradingSetting / GradeComponent models.
 *
 * The final is a weighted average over the inputs that actually have a value —
 * normalised by the total weight used, so a scheme whose weights don't sum to
 * exactly 100 still yields a sensible 0-scale grade rather than a scaled-down
 * one. A missing component score (or a missing attendance rate when attendance
 * is weighted) is excluded from the average and marks the result incomplete.
 */
class GradingEngine
{
    /**
     * @param  array<int, float|int>  $componentWeights  [componentId => weight]
     * @param  array<int, float|int|null>  $scores  [componentId => raw score, 0-based scale]
     * @param  float  $passingMark  pass threshold on the same scale as the scores
     * @param  float  $attendanceWeight  weight attendance contributes (0 = not counted)
     * @param  float|null  $attendanceRate  attendance as a 0-100 rate, or null if unknown
     */
    public function compute(
        array $componentWeights,
        array $scores,
        float $passingMark,
        float $attendanceWeight = 0.0,
        ?float $attendanceRate = null,
    ): GradeResult {
        $weightedSum = 0.0;
        $totalWeight = 0.0;
        $complete = true;

        foreach ($componentWeights as $componentId => $weight) {
            $weight = (float) $weight;
            if ($weight <= 0) {
                continue; // a zero-weight component is not part of the scheme
            }

            $score = $scores[$componentId] ?? null;
            if ($score === null) {
                $complete = false; // a weighted component with no score yet

                continue;
            }

            $weightedSum += (float) $score * $weight;
            $totalWeight += $weight;
        }

        if ($attendanceWeight > 0) {
            if ($attendanceRate === null) {
                $complete = false;
            } else {
                $weightedSum += $attendanceRate * $attendanceWeight;
                $totalWeight += $attendanceWeight;
            }
        }

        if ($totalWeight <= 0) {
            return GradeResult::empty();
        }

        $final = round($weightedSum / $totalWeight, 2);

        return new GradeResult(
            final: $final,
            passed: $final >= $passingMark,
            isComplete: $complete,
            weightUsed: $totalWeight,
        );
    }
}
