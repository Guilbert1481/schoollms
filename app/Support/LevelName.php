<?php

namespace App\Support;

/**
 * Bridges an education-structure node name to the academic_levels vocabulary.
 * Shared by LevelTreeResolver (matching) and LevelVocabularySync (provisioning)
 * so the two can never drift.
 */
class LevelName
{
    /**
     * Lowercased key used to match/dedupe a node name against academic_levels.
     * Returns null for a name that carries no level of its own. "Grade 11 (Core)"
     * → "grade 11"; "Kindergarten" → "kinder" (reuse the seeded row); any other
     * offered level (Toddler, Nursery, …) → its plain lowercased name.
     */
    public static function key(string $name): ?string
    {
        $n = self::clean($name);

        if ($n === '') {
            return null;
        }

        if (preg_match('/^kinder/i', $n)) {
            return 'kinder';
        }

        return mb_strtolower($n);
    }

    /**
     * Human-readable name to store when provisioning a new academic_levels row.
     */
    public static function display(string $name): string
    {
        return self::clean($name);
    }

    /** Trim, drop a trailing "(qualifier)", and collapse inner whitespace. */
    private static function clean(string $name): string
    {
        $n = (string) preg_replace('/\s*\([^)]*\)\s*$/', '', trim($name));

        return (string) preg_replace('/\s+/', ' ', trim($n));
    }
}
