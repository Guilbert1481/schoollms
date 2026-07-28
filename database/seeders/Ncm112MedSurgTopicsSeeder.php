<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeds the Medical-Surgical Nursing lecture topics under NCM 112.
 *
 * Under CMO 15 s. 2017, "Medical-Surgical Nursing" is not a standalone subject —
 * its content lives in NCM 112 (Care of Clients with Problems in Oxygenation,
 * Fluid & Electrolyte, Infectious, Inflammatory, Immunologic Response & Cellular
 * Aberration). These topics mirror a real Med-Surg lecture set (cardiovascular,
 * respiratory, renal, fluid/electrolyte/acid-base, hematologic, perioperative)
 * and attach as a flat topic list — no sub-levels.
 *
 * Depends on BsNursingCurriculumSeeder having created NCM 112. Resolves that
 * subject by code and fails loudly if absent.
 *
 * Idempotent: topics resolve by (school_id, subject_id, name); re-running updates
 * sort order in place rather than duplicating.
 *
 *     php artisan db:seed --class=Ncm112MedSurgTopicsSeeder
 */
class Ncm112MedSurgTopicsSeeder extends Seeder
{
    private const SCHOOL_ID = 1;

    private const SUBJECT_CODE = 'NCM-112';

    public function run(): void
    {
        $subjectId = DB::table('subjects')
            ->where('school_id', self::SCHOOL_ID)
            ->where('code', self::SUBJECT_CODE)
            ->where('is_basic_ed', 0)
            ->value('id');

        if (! $subjectId) {
            throw new RuntimeException(
                'NCM-112 subject not found for school '.self::SCHOOL_ID.
                '. Run BsNursingCurriculumSeeder first.'
            );
        }

        $now = now();
        $order = 0;

        foreach ($this->topics() as $name) {
            $order++;
            DB::table('topics')->updateOrInsert(
                ['school_id' => self::SCHOOL_ID, 'subject_id' => $subjectId, 'name' => $name],
                [
                    'sort_order' => $order,
                    'sequence' => $order,
                    'is_active' => 1,
                    'description' => 'Medical-Surgical Nursing lecture topic',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $this->command?->info(sprintf(
            'Seeded %d Medical-Surgical topics under NCM 112 (subject %d).',
            count($this->topics()),
            $subjectId,
        ));
    }

    /**
     * Flat Med-Surg lecture topics, grouped by body system for a sensible order.
     *
     * @return array<int, string>
     */
    protected function topics(): array
    {
        return [
            // Cardiovascular
            'Anatomy of the Heart',
            'Cardiac Fundamentals',
            'Cardiovascular Diagnostic Procedures',
            'Hypertension',
            'Angina Pectoris',
            'Cardiomyopathy',
            'Congestive Heart Failure (CHF)',
            'Aneurysm',
            "Raynaud's Disease",

            // Respiratory
            'Disorders of the Respiratory System',
            'Respiratory Diagnostic Procedures and Treatment Modalities',
            'Asthma',
            'Acute Respiratory Failure',

            // Renal / fluids / acid-base
            'Renal Disorders',
            'Fluids and Electrolytes',
            'Acid-Base Balance and Imbalances',

            // Hematologic
            'Blood Disorders',

            // Perioperative
            'Perioperative Nursing Concepts',
        ];
    }
}
