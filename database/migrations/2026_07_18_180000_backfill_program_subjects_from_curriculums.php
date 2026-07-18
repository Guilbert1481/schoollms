<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data repair: a subject listed in a curriculum must also be linked to that
     * curriculum's program via `program_subjects`.
     *
     * Nine subjects (7 BSEd-English majors plus Environmental Science and Assessment
     * in Learning 2) sat in "BSED English Curriculum 2024" with no program_subjects
     * row. Since higher-ed subjects reach the Course Architect's Lesson Studio only
     * through program_subjects → programs.education_node_id, those subjects were
     * unreachable from every tab — invisible rather than merely unfiltered.
     *
     * The curriculum row is the authoritative statement of "this subject belongs to
     * this program", so the link is derived from it rather than guessed. Idempotent:
     * only pairs that don't already exist are inserted.
     */
    public function up(): void
    {
        $missing = DB::table('curriculum_subjects as cs')
            ->join('curriculums as c', 'c.id', '=', 'cs.curriculum_id')
            ->whereNotNull('c.program_id')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('program_subjects as ps')
                    ->whereColumn('ps.subject_id', 'cs.subject_id')
                    ->whereColumn('ps.program_id', 'c.program_id');
            })
            ->distinct()
            ->get(['cs.subject_id', 'c.program_id']);

        if ($missing->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('program_subjects')->insertOrIgnore(
            $missing->map(fn ($r) => [
                'subject_id' => $r->subject_id,
                'program_id' => $r->program_id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    public function down(): void
    {
        // Not reversible: the inserted rows are indistinguishable from the links that
        // were already correct, so removing them would delete legitimate mappings.
    }
};
