<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Activates / deactivates program_subjects.is_active based on:
 *   - Activation: an enrollment_settings row exists for the term whose
 *                 start_date <= today (enrollment is open).
 *   - Deactivation: the term's end_date has passed (semester ended).
 *
 * Matching is by school + semester_number (1st/2nd) parsed from terms.term.
 */
class ProgramSubjectActivationService
{
    /**
     * Map terms.term string ("1st Semester", "2nd Semester", ...) to
     * program_subjects.semester_number (1, 2, ...).
     */
    public function semesterNumberFromTerm(?string $term): ?int
    {
        if (! $term) {
            return null;
        }

        $t = strtolower($term);

        if (str_contains($t, '1st') || str_contains($t, 'first')) {
            return 1;
        }
        if (str_contains($t, '2nd') || str_contains($t, 'second')) {
            return 2;
        }
        if (str_contains($t, '3rd') || str_contains($t, 'third') || str_contains($t, 'summer')) {
            return 3;
        }

        return null;
    }

    /**
     * Run the full sync.
     *
     * @return array{activated:int, deactivated:int}
     */
    public function sync(): array
    {
        $today = Carbon::today();
        $activated = 0;
        $deactivated = 0;

        // ---------- ACTIVATION ----------
        // Open enrollment sessions: today between enrollment_settings.start_date and term.end_date,
        // AND the term itself has not ended yet.
        $openSessions = DB::table('enrollment_settings as es')
            ->join('terms as t', 't.id', '=', 'es.term_id')
            ->whereDate('es.start_date', '<=', $today)
            ->whereDate('t.end_date', '>=', $today)
            ->select('t.id as term_id', 't.school_id', 't.term as term_label')
            ->get();

        foreach ($openSessions as $row) {
            $semNum = $this->semesterNumberFromTerm($row->term_label);
            if (! $semNum) {
                continue;
            }

            $count = DB::table('program_subjects')
                ->whereIn('program_id', function ($q) use ($row) {
                    $q->select('id')->from('programs')->where('school_id', $row->school_id);
                })
                ->where('semester_number', $semNum)
                ->where('is_active', 0)
                ->update(['is_active' => 1, 'updated_at' => now()]);

            $activated += $count;
        }

        // ---------- DEACTIVATION ----------
        // Any term whose end_date has passed -> turn off program_subjects for that
        // school + semester_number (unless another open session covers the same slot).
        $endedTerms = DB::table('terms')
            ->whereDate('end_date', '<', $today)
            ->select('id', 'school_id', 'term as term_label', 'end_date')
            ->get();

        foreach ($endedTerms as $term) {
            $semNum = $this->semesterNumberFromTerm($term->term_label);
            if (! $semNum) {
                continue;
            }

            // Skip if another term for the same school+semester is still open.
            $stillOpen = DB::table('terms')
                ->where('school_id', $term->school_id)
                ->where('term', $term->term_label)
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($stillOpen) {
                continue;
            }

            $count = DB::table('program_subjects')
                ->whereIn('program_id', function ($q) use ($term) {
                    $q->select('id')->from('programs')->where('school_id', $term->school_id);
                })
                ->where('semester_number', $semNum)
                ->where('is_active', 1)
                ->update(['is_active' => 0, 'updated_at' => now()]);

            $deactivated += $count;
        }

        if ($activated || $deactivated) {
            Log::info('ProgramSubjectActivationService sync', [
                'activated' => $activated,
                'deactivated' => $deactivated,
            ]);
        }

        return [
            'activated' => $activated,
            'deactivated' => $deactivated,
        ];
    }
}
