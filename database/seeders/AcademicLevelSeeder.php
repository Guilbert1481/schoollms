<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the standard `academic_levels` vocabulary (see ADR-0006).
 *
 * These rows are backend plumbing for questions/tests tagging; the UI always
 * says "Grade Level" / "Year Level". Idempotent — safe to re-run (upserts on
 * the (name, type) unique key).
 */
class AcademicLevelSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [];
        $now  = now();

        // Basic education: Kinder + Grade 1–12.
        $rows[] = ['name' => 'Kinder', 'sequence_order' => 0, 'type' => 'basic'];
        for ($g = 1; $g <= 12; $g++) {
            $rows[] = ['name' => 'Grade '.$g, 'sequence_order' => $g, 'type' => 'basic'];
        }

        // Higher education: Year 1–5 (5 covers 5-year programs).
        for ($y = 1; $y <= 5; $y++) {
            $rows[] = ['name' => 'Year '.$y, 'sequence_order' => $y, 'type' => 'higher'];
        }

        // Non-academic session types.
        $rows[] = ['name' => 'Training', 'sequence_order' => 1, 'type' => 'training'];
        $rows[] = ['name' => 'Review',   'sequence_order' => 1, 'type' => 'review'];

        // The vocabulary is per-school (unique on school_id+name+type), so seed
        // the full set for every school.
        foreach (DB::table('schools')->pluck('id') as $schoolId) {
            foreach ($rows as $row) {
                DB::table('academic_levels')->updateOrInsert(
                    ['school_id' => $schoolId, 'name' => $row['name'], 'type' => $row['type']],
                    $row + ['school_id' => $schoolId, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
}
