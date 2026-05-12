<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repair migration.
 *
 * The previous migration (2026_04_23_110000_add_school_id_to_academic_levels_table)
 * replicated academic_levels per school but did NOT repoint dependent rows.
 * Result: questions whose subject lives in school A may still point to an
 * academic_level row now owned by school B.
 *
 * This migration remaps questions.academic_level_id so that every question
 * references a level row owned by the same school as its subject.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('questions') || !Schema::hasColumn('questions', 'academic_level_id')) {
            return;
        }

        // Build a (school_id, name, type) -> id lookup for current levels.
        $levelsBySchool = DB::table('academic_levels')
            ->get(['id', 'school_id', 'name', 'type'])
            ->groupBy(function ($r) {
                return $r->school_id . '|' . strtolower(trim($r->name)) . '|' . strtolower(trim($r->type));
            });

        // Pull every question with its subject's school_id and current level info.
        $rows = DB::table('questions as q')
            ->join('subjects as s', 's.id', '=', 'q.subject_id')
            ->join('academic_levels as al', 'al.id', '=', 'q.academic_level_id')
            ->select('q.id as question_id', 's.school_id as target_school', 'al.name', 'al.type', 'al.school_id as current_school')
            ->get();

        $fixed = 0;
        $missing = [];

        foreach ($rows as $r) {
            if ($r->current_school == $r->target_school) {
                continue; // already correct
            }

            $key = $r->target_school . '|' . strtolower(trim($r->name)) . '|' . strtolower(trim($r->type));
            $match = $levelsBySchool->get($key);

            if (!$match || $match->isEmpty()) {
                $missing[] = "question {$r->question_id}: no level '{$r->name}' / '{$r->type}' in school {$r->target_school}";
                continue;
            }

            DB::table('questions')
                ->where('id', $r->question_id)
                ->update(['academic_level_id' => $match->first()->id]);
            $fixed++;
        }

        if ($fixed > 0) {
            echo "  [repair] remapped academic_level_id on {$fixed} question(s)\n";
        }
        foreach ($missing as $m) {
            echo "  [repair] WARNING {$m}\n";
        }
    }

    public function down(): void
    {
        // No-op: the remap is data-corrective and not safely reversible.
    }
};
