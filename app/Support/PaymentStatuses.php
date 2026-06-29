<?php

namespace App\Support;

/**
 * Finance payment-status taxonomy for the Student Ledgers list.
 *
 * Unlike App\Support\EnrollmentStatuses (which tracks a student's lifecycle),
 * this describes the state of a student's *account*: are they settled, do they
 * owe money, and is that money overdue? Colours are inline hex so the pills
 * render correctly regardless of the compiled Tailwind build.
 */
class PaymentStatuses
{
    /** key => [label, bg hex, fg hex]. Order drives the filter dropdown. */
    public const STATUSES = [
        'paid'     => ['label' => 'Paid',     'bg' => '#d1fae5', 'fg' => '#047857'],
        'current'  => ['label' => 'Current',  'bg' => '#e0f2fe', 'fg' => '#0369a1'],
        'due_soon' => ['label' => 'Due Soon', 'bg' => '#fef3c7', 'fg' => '#b45309'],
        'overdue'  => ['label' => 'Overdue',  'bg' => '#ffe4e6', 'fg' => '#be123c'],
    ];

    /** [key => label] for the Status filter dropdown. */
    public static function options(): array
    {
        return array_map(fn ($s) => $s['label'], self::STATUSES);
    }

    /** Is this a known status key? */
    public static function isValid(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::STATUSES);
    }

    public static function label(string $key): string
    {
        return self::STATUSES[$key]['label'] ?? ucwords(str_replace('_', ' ', $key));
    }

    /** Coloured pill (HTML) for the Status column. */
    public static function pill(string $key): string
    {
        $s = self::STATUSES[$key] ?? ['label' => self::label($key), 'bg' => '#f1f5f9', 'fg' => '#475569'];

        return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold" '
            .'style="background-color:'.$s['bg'].';color:'.$s['fg'].';">'.$s['label'].'</span>';
    }

    /**
     * Resolve a student's account status from their balance and invoice state.
     *
     * @param  float  $balance     Outstanding balance (charges − payments).
     * @param  bool   $hasOverdue  Any unpaid invoice past its due date.
     * @param  bool   $hasDueSoon  Any unpaid invoice due within the grace window.
     */
    public static function resolve(float $balance, bool $hasOverdue, bool $hasDueSoon): string
    {
        if ($balance <= 0.005) {
            return 'paid';
        }
        if ($hasOverdue) {
            return 'overdue';
        }
        if ($hasDueSoon) {
            return 'due_soon';
        }

        return 'current';
    }
}
