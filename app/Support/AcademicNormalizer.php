<?php

namespace App\Support;

use Illuminate\Support\Str;

class AcademicNormalizer
{
    /**
     * Normalize academic labels (subjects, topics, lessons)
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Normalize casing first
        $normalized = Str::title(Str::lower($value));

        /**
         * 1️⃣ HARD EXCEPTIONS (never singularize)
         */
        $exceptions = [
            'Statistics',
            'Mathematics',
            'Physics',
            'Economics',
            'Ethics',
            'Linguistics',
            'Politics',
            'Mechanics',
        ];

        if (in_array($normalized, $exceptions, true)) {
            return $normalized;
        }

        /**
         * 2️⃣ COMMON HUMAN MISTAKES → CANONICAL FORM
         */
        $corrections = [
            'Statistic'  => 'Statistics',
            'Mathematic' => 'Mathematics',
            'Physic'     => 'Physics',
            'Economic'   => 'Economics',
            'Politic'    => 'Politics',
            'Mechanic'   => 'Mechanics',
            'Ethic'      => 'Ethics',
        ];

        if (array_key_exists($normalized, $corrections)) {
            return $corrections[$normalized];
        }

        /**
         * 3️⃣ SAFE SINGULARIZATION
         */
        $singular = Str::singular($normalized);

        return Str::title($singular);
    }

    /**
     * Normalize array inputs (e.g. question types)
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
