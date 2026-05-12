<?php

namespace App\Helpers;

use App\Models\EnrollmentSetting;
use Illuminate\Support\Str;

class CourseCatalog
{
    /**
     * Resolve the catalog key for a given EnrollmentSetting.
     *
     * Matching order:
     *  1. Slug of the session's name / title (tries progressively shorter
     *     prefixes so "LET Review April 2026" → "let-review-april-2026"
     *     → "let-review-april" → "let-review" → "let").
     *  2. Slug of the EnrollmentType name (e.g. "Seminar" → "seminar").
     *  3. Aliases table.
     *  4. Fallback to `default_catalog`.
     */
    public static function resolveKeyFor(EnrollmentSetting $setting): string
    {
        $catalogs = config('course_catalog.catalogs', []);
        $aliases  = config('course_catalog.aliases', []);
        $default  = config('course_catalog.default_catalog', 'generic');

        $candidates = [];

        foreach ([$setting->title, $setting->name] as $value) {
            if (!$value) continue;
            $slug = Str::slug($value);
            $parts = explode('-', $slug);
            while (!empty($parts)) {
                $candidates[] = implode('-', $parts);
                array_pop($parts);
            }
        }

        if ($setting->enrollmentType?->name) {
            $candidates[] = Str::slug($setting->enrollmentType->name);
        }

        foreach ($candidates as $candidate) {
            if (isset($catalogs[$candidate])) {
                return $candidate;
            }
            $aliasKey = str_replace('-', '_', $candidate);
            if (isset($aliases[$aliasKey]) && isset($catalogs[$aliases[$aliasKey]])) {
                return $aliases[$aliasKey];
            }
            if (isset($aliases[$candidate]) && isset($catalogs[$aliases[$candidate]])) {
                return $aliases[$candidate];
            }
        }

        return isset($catalogs[$default]) ? $default : (array_key_first($catalogs) ?? 'generic');
    }

    public static function forSession(EnrollmentSetting $setting): array
    {
        $key = self::resolveKeyFor($setting);
        $catalog = config("course_catalog.catalogs.$key", []);
        $catalog['key'] = $key;
        return $catalog;
    }
}
