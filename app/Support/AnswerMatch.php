<?php

namespace App\Support;

/**
 * Compares a written (identification / matching) answer to the accepted answers.
 * Normalises case, whitespace and punctuation, then accepts an exact match — or,
 * for longer answers, a single-character slip (typo / OCR misread). Deliberately
 * conservative: short answers require an exact normalised match so "cat" never
 * grades "bat".
 */
class AnswerMatch
{
    /** Below this length, only an exact normalised match counts. */
    private const FUZZY_MIN_LEN = 5;

    /** Normalise for comparison: lowercase, strip punctuation, collapse spaces. */
    public static function normalise(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = (string) preg_replace('/[\p{P}\p{S}]+/u', ' ', $v); // punctuation/symbols → space
        $v = (string) preg_replace('/\s+/', ' ', $v);

        return trim($v);
    }

    /**
     * True when the student's answer matches any accepted answer.
     *
     * @param  array<int, string>  $accepted
     */
    public static function matches(string $student, array $accepted): bool
    {
        $s = self::normalise($student);
        if ($s === '') {
            return false;
        }

        foreach ($accepted as $answer) {
            $a = self::normalise($answer);
            if ($a === '') {
                continue;
            }
            if ($s === $a) {
                return true;
            }
            if (mb_strlen($a) >= self::FUZZY_MIN_LEN && levenshtein($s, $a) <= 1) {
                return true;
            }
        }

        return false;
    }
}
