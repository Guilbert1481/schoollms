<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Words ignored when building the ABBR. */
    private const FILLERS = [
        'of','and','the','in','to','a','an','for','at','on','by','from',
        'with','or','&','sa','ng','iba\'t','ibang','mga','as',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('subjects')) return;

        // --- 1. Pull every subject + its earliest year_level from program_subjects ---
        $rows = DB::table('subjects as s')
            ->leftJoin('program_subjects as ps', 'ps.subject_id', '=', 's.id')
            ->select('s.id', 's.code', 's.name', 's.category',
                DB::raw('MIN(ps.year_level) as year_level'))
            ->groupBy('s.id', 's.code', 's.name', 's.category')
            ->orderBy('s.id')
            ->get();

        // --- 2. Bucket subjects by (prefix, level) to assign sequence numbers ---
        $buckets = [];   // ["PREFIX|LEVEL" => [['id'=>.., 'abbr'=>..], ...]]
        $plans   = [];   // [subject_id => ['prefix'=>..,'abbr'=>..,'level'=>..]]

        foreach ($rows as $r) {
            $prefix = $this->prefixFor($r->category, (string) $r->code);
            $abbr   = $this->abbreviate($r->name) ?: 'GEN';
            $level  = (int) ($r->year_level ?: 1);

            $plans[$r->id] = compact('prefix', 'abbr', 'level');
            $buckets[$prefix . '|' . $level][] = $r->id;
        }

        // --- 3. Phase 1: park every code under a temporary value so we can
        //        rewrite without tripping the UNIQUE(code) constraint. ---
        DB::transaction(function () use ($plans) {
            foreach (array_keys($plans) as $id) {
                DB::table('subjects')->where('id', $id)
                    ->update(['code' => '__TMP_' . $id]);
            }
        });

        // --- 4. Phase 2: write final codes, sequence per (prefix, level) bucket ---
        DB::transaction(function () use ($buckets, $plans) {
            foreach ($buckets as $key => $ids) {
                [$prefix, $level] = explode('|', $key);
                $seq = 1;
                foreach ($ids as $id) {
                    $abbr = $plans[$id]['abbr'];
                    $code = sprintf('%s-%s-%d%02d', $prefix, $abbr, (int) $level, $seq);
                    DB::table('subjects')->where('id', $id)->update(['code' => $code]);
                    $seq++;
                }
            }
        });
    }

    public function down(): void
    {
        // No-op: original codes are not preserved. Rolling back would require
        // a snapshot, which we deliberately do not store here.
    }

    private function prefixFor(?string $category, string $existingCode): string
    {
        $existing = strtoupper($existingCode);

        switch ($category) {
            case 'gen_ed':     return 'GEN';
            case 'prof_ed':    return 'PROF';
            case 'pe':         return 'PE';
            case 'nstp':       return 'NSTP';
            case 'internship': return 'INT';
            case 'major':
                if (str_starts_with($existing, 'MATH')) return 'MATH';
                if (str_starts_with($existing, 'ENG'))  return 'ENG';
                if (str_starts_with($existing, 'LET'))  return 'LET';
                return 'MAJOR';
        }

        return 'GEN';
    }

    /**
     * Derive a 2–4 uppercase-letter abbreviation from a subject name.
     * - Strips filler words and non-letter characters.
     * - Single significant word  → first 4 letters.
     * - Multiple significant words → first letter of each (max 4).
     */
    private function abbreviate(string $name): string
    {
        // Remove parenthetical suffixes like "(English)" so they don't dominate.
        $cleaned = preg_replace('/\([^)]*\)/', ' ', $name);

        // Split on any non-letter (preserves apostrophe-stripped tokens).
        $tokens = preg_split('/[^A-Za-z]+/', (string) $cleaned, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $significant = [];
        foreach ($tokens as $t) {
            $low = strtolower($t);
            if (in_array($low, self::FILLERS, true)) continue;
            if (strlen($t) < 1) continue;
            $significant[] = $t;
        }

        if (count($significant) === 0) return '';

        if (count($significant) === 1) {
            return strtoupper(substr($significant[0], 0, 4));
        }

        $abbr = '';
        foreach ($significant as $w) {
            $abbr .= strtoupper(substr($w, 0, 1));
            if (strlen($abbr) >= 4) break;
        }

        // Need at least 2 chars; if not, pad with first word's letters.
        if (strlen($abbr) < 2) {
            $abbr = strtoupper(substr($significant[0], 0, 2));
        }

        return $abbr;
    }
};
