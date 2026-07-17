<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Test;
use Illuminate\Support\Facades\DB;

/**
 * Builds the centered letterhead lines (academic year, education level, and —
 * for higher education — the semester) for a printed test / answer key.
 *
 * These are read from the enrolled roster of the test's section: the enrollment
 * points at a `terms` row, which is the source of truth for basic_ed vs
 * higher_ed and for the semester label. (The Semester model targets a
 * `semesters` table that does not exist, so it can't be used here.) A test with
 * no class/section — a personal test — degrades gracefully: only the lines that
 * can be derived are returned, the rest come back null.
 */
class TestPrintHeader
{
    /**
     * Derived header lines for the print/answer-key letterhead. The school name
     * (line 1), subject, and assessment type are already trivially available in
     * the view, so only the enrollment-derived lines are computed here.
     *
     * @return array{academicYear: ?string, level: ?string, semester: ?string}
     */
    public static function for(Test $test): array
    {
        $section = $test->class?->section;

        if (! $section) {
            return ['academicYear' => null, 'level' => null, 'semester' => null];
        }

        $enrollment = DB::table('student_enrollments')
            ->where('section_id', $section->id)
            ->where('status', 'enrolled')
            ->orderBy('id')
            ->first(['academic_year_id', 'term_id', 'education_level', 'program_id']);

        $term = $enrollment && $enrollment->term_id
            ? DB::table('terms')->find($enrollment->term_id)
            : null;

        $academicYear = self::academicYear($test, $enrollment->academic_year_id ?? null);
        $yearLevel = trim((string) $section->year_level);
        $educationLevel = $term->education_level ?? $enrollment->education_level ?? '';

        if (self::isBasic($educationLevel)) {
            return [
                'academicYear' => $academicYear,
                'level' => 'Basic Education'.($yearLevel !== '' ? ' - Grade '.$yearLevel : ''),
                'semester' => null,
            ];
        }

        $name = self::higherEdName($enrollment, $term);

        return [
            'academicYear' => $academicYear,
            'level' => $name.($yearLevel !== '' ? ' - Year '.$yearLevel : ''),
            'semester' => $term->term ?? null, // e.g. "1st Semester" — higher ed only
        ];
    }

    /** Academic-year name for the enrollment's year, falling back to the school's active year. */
    private static function academicYear(Test $test, ?int $academicYearId): ?string
    {
        if ($academicYearId && ($name = AcademicYear::whereKey($academicYearId)->value('name'))) {
            return $name;
        }

        return AcademicYear::where('school_id', $test->school_id)->where('is_active', true)->value('name')
            ?? AcademicYear::where('school_id', $test->school_id)->value('name');
    }

    /**
     * Basic education (preschool → senior high) vs higher education. Reads the
     * `terms.education_level` marker ('basic_ed' / 'higher_ed'); higher-ed
     * keywords flip it to non-basic. Unknown/blank defaults to basic — K-12 is
     * the common case and the safer default for the "no semester line" rule.
     */
    private static function isBasic(string $educationLevel): bool
    {
        $level = strtolower($educationLevel);

        if ($level === '') {
            return true;
        }

        return ! (bool) preg_match('/higher|college|under|grad|tertiary/', $level);
    }

    /** Program/college name shown on the level line for a higher-ed test. */
    private static function higherEdName(?object $enrollment, ?object $term): string
    {
        if ($enrollment && $enrollment->program_id
            && ($name = DB::table('programs')->where('id', $enrollment->program_id)->value('name'))) {
            return $name;
        }

        if ($term && ($term->education_node_id ?? null)
            && ($name = DB::table('education_nodes')->where('id', $term->education_node_id)->value('name'))) {
            return $name;
        }

        return 'Higher Education';
    }
}
