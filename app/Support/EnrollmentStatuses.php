<?php

namespace App\Support;

/**
 * Canonical student / enrollment status taxonomy — the single source of truth
 * for the coloured status pills, the status filter dropdowns and the mapping
 * back to the underlying database columns.
 *
 * Two groups:
 *   - 'validate' : statuses BEFORE a student is officially enrolled. Used by the
 *                  Registrar's "Validate Enrollment" page. Resolved from the
 *                  student_enrollments.status column.
 *   - 'ledger'   : the officially-enrolled status plus every post-enrollment
 *                  lifecycle status. Used by "Student Ledgers". Resolved first
 *                  from students.status (a student-level lifecycle field) and,
 *                  failing that, from student_enrollments.status.
 *
 * Each status maps to:
 *   - db  : student_enrollments.status values that resolve to it
 *   - sdb : students.status values that resolve to it
 *
 * Statuses with empty db/sdb sets (On Leave, Transferred, Alumni, Expelled,
 * Deceased, Archived…) are intentionally listed: they are valid options the
 * school can grow into. They simply match no rows until that status is recorded.
 *
 * Colours are inline (hex) on purpose so the pills always render regardless of
 * the Tailwind purge/build.
 */
final class EnrollmentStatuses
{
    /**
     * key => [label, group, db[], sdb[], bg, fg, dot]
     */
    public const STATUSES = [
        'enrolled' => [
            'label' => 'Enrolled', 'group' => 'ledger',
            'db' => ['enrolled'], 'sdb' => [],
            'bg' => '#d1fae5', 'fg' => '#065f46', 'dot' => '#10b981',
        ],
        'pre_enrolled' => [
            'label' => 'Pre-Enrolled', 'group' => 'validate',
            'db' => ['provisionally_enrolled', 'assessed', 'sent_billing', 'billed', 'partially_paid', 'exam_passed'],
            'sdb' => [],
            'bg' => '#dbeafe', 'fg' => '#1e40af', 'dot' => '#3b82f6',
        ],
        'pending_requirements' => [
            'label' => 'Pending Requirements', 'group' => 'validate',
            'db' => ['submitted', 'provisional'], 'sdb' => [],
            'bg' => '#fef3c7', 'fg' => '#92400e', 'dot' => '#f59e0b',
        ],
        'on_leave' => [
            'label' => 'On Leave', 'group' => 'ledger',
            'db' => [], 'sdb' => ['on_leave', 'loa'],
            'bg' => '#ffedd5', 'fg' => '#9a3412', 'dot' => '#f97316',
        ],
        'withdrawn' => [
            'label' => 'Withdrawn', 'group' => 'ledger',
            'db' => ['cancelled'], 'sdb' => ['withdrawn'],
            'bg' => '#fee2e2', 'fg' => '#991b1b', 'dot' => '#ef4444',
        ],
        'dropped' => [
            'label' => 'Dropped', 'group' => 'ledger',
            'db' => ['dropped'], 'sdb' => ['dropped'],
            'bg' => '#e5e7eb', 'fg' => '#374151', 'dot' => '#6b7280',
        ],
        'transferred_out' => [
            'label' => 'Transferred Out', 'group' => 'ledger',
            'db' => [], 'sdb' => ['transferred_out', 'transferred'],
            'bg' => '#f3e8ff', 'fg' => '#6b21a8', 'dot' => '#a855f7',
        ],
        'transferred_in' => [
            'label' => 'Transferred In', 'group' => 'ledger',
            'db' => [], 'sdb' => ['transferred_in'],
            'bg' => '#e0f2fe', 'fg' => '#075985', 'dot' => '#0ea5e9',
        ],
        'graduated' => [
            'label' => 'Graduated', 'group' => 'ledger',
            'db' => ['completed', 'graduated'], 'sdb' => ['graduated'],
            'bg' => '#fef3c7', 'fg' => '#713f12', 'dot' => '#a16207',
        ],
        'alumni' => [
            'label' => 'Alumni', 'group' => 'ledger',
            'db' => [], 'sdb' => ['alumni'],
            'bg' => '#f3f4f6', 'fg' => '#4b5563', 'dot' => '#9ca3af',
        ],
        'expelled' => [
            'label' => 'Expelled', 'group' => 'ledger',
            'db' => [], 'sdb' => ['expelled'],
            'bg' => '#1f2937', 'fg' => '#f9fafb', 'dot' => '#111827',
        ],
        'deceased' => [
            'label' => 'Deceased', 'group' => 'ledger',
            'db' => [], 'sdb' => ['deceased'],
            'bg' => '#111827', 'fg' => '#f9fafb', 'dot' => '#000000',
        ],
        'archived' => [
            'label' => 'Archived', 'group' => 'ledger',
            'db' => [], 'sdb' => ['archived'],
            'bg' => '#f9fafb', 'fg' => '#6b7280', 'dot' => '#9ca3af',
        ],
    ];

    /**
     * Dropdown options for a group: ['enrolled' => 'Enrolled', …].
     */
    public static function options(string $group): array
    {
        $out = [];
        foreach (self::STATUSES as $key => $s) {
            if ($s['group'] === $group) {
                $out[$key] = $s['label'];
            }
        }

        return $out;
    }

    /** student_enrollments.status values for a taxonomy key. */
    public static function dbValuesFor(string $key): array
    {
        return self::STATUSES[$key]['db'] ?? [];
    }

    /** students.status values for a taxonomy key. */
    public static function studentDbValuesFor(string $key): array
    {
        return self::STATUSES[$key]['sdb'] ?? [];
    }

    /**
     * Union of all student_enrollments.status values used by a group — handy for
     * scoping a page to "everything in this group" (e.g. all pre-official rows).
     */
    public static function dbValuesForGroup(string $group): array
    {
        $vals = [];
        foreach (self::STATUSES as $s) {
            if ($s['group'] === $group) {
                $vals = array_merge($vals, $s['db']);
            }
        }

        return array_values(array_unique($vals));
    }

    /** Reverse lookup: enrollment status string -> taxonomy key. */
    public static function keyForDb(?string $dbStatus): ?string
    {
        if (! $dbStatus) {
            return null;
        }
        foreach (self::STATUSES as $key => $s) {
            if (in_array($dbStatus, $s['db'], true)) {
                return $key;
            }
        }

        return null;
    }

    /** Reverse lookup: students.status string -> taxonomy key. */
    public static function keyForStudent(?string $studentStatus): ?string
    {
        if (! $studentStatus) {
            return null;
        }
        foreach (self::STATUSES as $key => $s) {
            if (in_array($studentStatus, $s['sdb'], true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Resolve the taxonomy key for a Student-Ledgers row: the student-level
     * status wins (graduated, on_leave, …) and the enrollment status is the
     * fallback (enrolled, pre_enrolled, …).
     */
    public static function resolveKey(?string $studentStatus, ?string $enrollmentStatus): ?string
    {
        return self::keyForStudent($studentStatus) ?? self::keyForDb($enrollmentStatus);
    }

    /** Coloured pill HTML for a taxonomy key. */
    public static function pill(?string $key): string
    {
        $s = $key ? (self::STATUSES[$key] ?? null) : null;
        if (! $s) {
            return self::rawPill($key);
        }

        return self::render($s['label'], $s['bg'], $s['fg'], $s['dot']);
    }

    /** Pill for a Student-Ledgers row (student status preferred, enrollment fallback). */
    public static function pillFor(?string $studentStatus, ?string $enrollmentStatus): string
    {
        $key = self::resolveKey($studentStatus, $enrollmentStatus);
        if ($key) {
            return self::pill($key);
        }

        return self::rawPill($studentStatus ?: $enrollmentStatus);
    }

    /** Pill from an enrollment status string only (used by Validate Enrollment). */
    public static function pillForDb(?string $dbStatus): string
    {
        $key = self::keyForDb($dbStatus);

        return $key ? self::pill($key) : self::rawPill($dbStatus);
    }

    /** Neutral pill for an unrecognised raw status — humanised, never blank. */
    private static function rawPill(?string $raw): string
    {
        $label = $raw ? ucwords(str_replace('_', ' ', $raw)) : 'Unknown';

        return self::render($label, '#f1f5f9', '#475569', '#94a3b8');
    }

    private static function render(string $label, string $bg, string $fg, string $dot): string
    {
        return '<span style="display:inline-flex;align-items:center;gap:5px;border-radius:9999px;'
            .'padding:2px 9px;font-size:11px;font-weight:700;line-height:1.5;white-space:nowrap;'
            .'background:'.$bg.';color:'.$fg.';">'
            .'<span style="width:7px;height:7px;border-radius:9999px;flex:none;background:'.$dot.';"></span>'
            .e($label).'</span>';
    }
}
