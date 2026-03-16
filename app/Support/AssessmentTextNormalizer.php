<?php

namespace App\Support;

class AssessmentTextNormalizer
{
    /**
     * Normalize assessment text safely.
     *
     * Rules:
     * - Trim leading and trailing spaces
     * - Collapse multiple spaces into one
     * - Capitalize FIRST character only
     * - Do NOT change words, grammar, plurality, or punctuation
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Trim leading and trailing whitespace
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Collapse multiple spaces into a single space
        $value = preg_replace('/\s+/', ' ', $value);

        // Capitalize FIRST character only (UTF-8 safe)
        $firstChar = mb_substr($value, 0, 1);
        $rest      = mb_substr($value, 1);

        return mb_strtoupper($firstChar) . $rest;
    }

    /**
     * Normalize array values (e.g. MCQ options)
     */
    public static function normalizeArray(array $values): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn ($value) => self::normalize($value),
                    $values
                )
            )
        );
    }
}
