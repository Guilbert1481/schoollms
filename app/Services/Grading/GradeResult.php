<?php

namespace App\Services\Grading;

/**
 * The outcome of one grade computation. Immutable value object.
 *
 * `final` is the weighted grade on the scheme's scale (null when there is
 * nothing to compute — no weighted components and no attendance weight, or no
 * scores at all). `isComplete` is true only when every weighted input had a
 * value, so a caller can distinguish a finished grade from an in-progress one
 * and refuse to persist a partial grade as final.
 */
final class GradeResult
{
    public function __construct(
        public readonly ?float $final,
        public readonly ?bool $passed,
        public readonly bool $isComplete,
        public readonly float $weightUsed,
    ) {}

    public static function empty(): self
    {
        return new self(null, null, false, 0.0);
    }
}
