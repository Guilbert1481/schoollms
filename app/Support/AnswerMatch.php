<?php

namespace App\Support;

/**
 * Compares a written (identification / fib / matching) answer to the accepted answers.
 *
 * Text answers: normalises case, whitespace and punctuation, then accepts an exact
 * match — or, for longer answers, a single-character slip (typo / OCR misread).
 * Deliberately conservative: short answers require an exact normalised match so
 * "cat" never grades "bat".
 *
 * Math answers (detected per accepted answer): the text normaliser strips ALL
 * symbols, which would grade "x+1" and "x-1" as identical. When an accepted answer
 * looks mathematical, it is compared with operators preserved, Unicode variants
 * unified (× → *, ² → ^2, ½ → 1/2 …) and whitespace removed — and never fuzzily,
 * because a one-character slip in math is simply a wrong answer.
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

    /** Normalise a math answer: unify Unicode variants, keep operators, drop spaces. */
    public static function normaliseMath(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = strtr($v, [
            '−' => '-', '–' => '-', '—' => '-',
            '×' => '*', '·' => '*', '⋅' => '*',
            '÷' => '/', '⁄' => '/',
            '²' => '^2', '³' => '^3',
            '½' => '1/2', '¼' => '1/4', '¾' => '3/4',
            '≤' => '<=', '≥' => '>=', '≠' => '!=',
            '（' => '(', '）' => ')',
        ]);

        return (string) preg_replace('/\s+/u', '', $v);
    }

    /**
     * Heuristic: does this accepted answer look like a math expression?
     * Hyphenated words ("mother-in-law", "t-shirt") must stay on the text path,
     * so a bare "-" only counts next to a digit or between lone-letter variables.
     */
    public static function looksMath(string $value): bool
    {
        if (preg_match('/[=^√∛∜×÷±≤≥≠²³½¼¾π∞∫]/u', $value)) {
            return true;
        }
        if (preg_match('~(\d|(?<![a-z])[a-z](?![a-z]))\s*[+*/]|[+*/]\s*(\d|(?<![a-z])[a-z](?![a-z]))~i', $value)) {
            return true;
        }
        if (preg_match('/\d\s*-|-\s*\d/', $value)) {
            return true;
        }

        return (bool) preg_match('/(?<![a-z])[a-z]\s*-\s*[a-z](?![a-z])/i', $value);
    }

    /**
     * True when the student's answer matches any accepted answer.
     *
     * @param  array<int, string>  $accepted
     */
    public static function matches(string $student, array $accepted): bool
    {
        $s = self::normalise($student);
        $sMath = self::normaliseMath($student);

        foreach ($accepted as $answer) {
            if (self::looksMath($answer)) {
                $a = self::normaliseMath($answer);
                if ($a !== '' && $sMath === $a) {
                    return true;
                }

                continue; // math never matches fuzzily
            }

            $a = self::normalise($answer);
            if ($a === '' || $s === '') {
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
