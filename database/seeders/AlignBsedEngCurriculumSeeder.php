<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Aligns the BSEDENG (program_id=2) curriculum to the canonical 4-year, 3-term
 * subject map. Idempotent — safe to re-run.
 *
 *  - Renames existing subject codes/names where they have drifted.
 *  - Creates any subjects that don't yet exist.
 *  - Rebuilds `program_subjects` rows for program 2 to match the canonical map.
 */
class AlignBsedEngCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $programId = 2;
        $schoolId = (int) (DB::table('programs')->where('id', $programId)->value('school_id') ?? 1);

        // 1) Code/name normalizations on the global subjects table.
        $renames = [
            // existing_code => [new_code, new_name|null, units]
            'ENG-LING'  => ['ENG-LING1',      'Introduction to Linguistics', 3],
            'ENG-STRC'  => ['ENG-STRUCT',     'Structure of English', 3],
            'ENG-SEAL'  => ['ENG-LIT1',       'Survey of English and American Literature', 3],
            'ENG-AFRO'  => ['ENG-LIT2',       'Afro-Asian Literature', 3],
            'ENG-STYL'  => ['ENG-STYLISTICS', 'Stylistics and Discourse Analysis', 3],
            'ENG-CHILD' => ['ENG-CALIT',      'Children and Adolescent Literature', 3],
            'ENG-LIT'   => ['ENG-TLIT',       'Teaching and Assessment of Literature Studies', 3],
            'ENG-CAMPJ' => ['ENG-JOURN',      'Campus Journalism', 3],
            'ENG-LLMD'  => ['ENG-MATDEV',     'Instructional Materials Development (English)', 3],
        ];

        foreach ($renames as $oldCode => [$newCode, $newName, $units]) {
            $row = DB::table('subjects')->where('code', $oldCode)->first();
            if (! $row) continue;
            DB::table('subjects')->where('id', $row->id)->update([
                'code' => $newCode,
                'name' => $newName,
                'units' => $row->units ?? $units,
                'updated_at' => now(),
            ]);
        }

        // 2) Create any missing subjects.
        $needed = [
            ['ENG-LAL',    'Language Acquisition and Learning', 3],
            ['ENG-WLIT',   'World Literature', 3],
            ['ENG-CW',     'Creative Writing', 3],
            ['ENG-RES',    'English Research', 3],
            ['ENG-TESS',   'Teaching English in Secondary Schools', 3],
            ['ENG-ASSESS', 'Assessment of Learning in English', 3],
            ['ENG-CURR',   'English Curriculum and Instruction', 3],
        ];

        foreach ($needed as [$code, $name, $units]) {
            $exists = DB::table('subjects')->where('code', $code)->exists();
            if ($exists) continue;
            DB::table('subjects')->insert([
                'school_id' => $schoolId,
                'code' => $code,
                'name' => $name,
                'units' => $units,
                'is_active' => 1,
                'scope' => 'academic',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3) Canonical map: [year_level, semester_number, code]
        $map = [
            // Year 1
            [1, 1, 'GEC-USELF'],   [1, 1, 'GEC-PURCO'], [1, 1, 'FIL-1'],
            [1, 1, 'PROF-CALL'],   [1, 1, 'PE-1'],
            [1, 2, 'GEC-MMW'],     [1, 2, 'GEC-RPH'],   [1, 2, 'PROF-TP'],
            [1, 2, 'NSTP-1'],      [1, 2, 'PE-2'],
            [1, 3, 'GEC-TCW'],     [1, 3, 'FIL-2'],     [1, 3, 'ENG-LING1'],
            [1, 3, 'NSTP-2'],      [1, 3, 'PE-3'],
            // Year 2
            [2, 1, 'GEC-ART'],     [2, 1, 'PROF-FSIE'], [2, 1, 'ENG-STRUCT'],
            [2, 1, 'ENG-LCS'],     [2, 1, 'PE-4'],
            [2, 2, 'GEC-ETH'],     [2, 2, 'PROF-FLCT'], [2, 2, 'ENG-LIT1'],
            [2, 2, 'ENG-LAL'],     [2, 2, 'GEC-STS'],
            [2, 3, 'RIZAL'],       [2, 3, 'PROF-TCSC'], [2, 3, 'PROF-TTL1'],
            [2, 3, 'PROF-TSC'],    [2, 3, 'ENG-LIT2'],
            // Year 3
            [3, 1, 'ENG-MYTH'],    [3, 1, 'ENG-STYLISTICS'], [3, 1, 'PROF-AL1'],
            [3, 1, 'GE-LITERA'],   [3, 1, 'PROF-RES1'],
            [3, 2, 'ENG-CALIT'],   [3, 2, 'ENG-LER'],   [3, 2, 'PROF-NLAC'],
            [3, 2, 'GE-GENDR'],    [3, 2, 'PROF-RES2'],
            [3, 3, 'ENG-WLIT'],    [3, 3, 'ENG-CW'],    [3, 3, 'ENG-TLIT'],
            [3, 3, 'ENG-TTL2'],    [3, 3, 'ENG-RES'],
            // Year 4
            [4, 1, 'FS-1'],        [4, 1, 'ENG-TESS'],  [4, 1, 'ENG-ASSESS'],
            [4, 2, 'FS-2'],        [4, 2, 'ENG-JOURN'], [4, 2, 'ENG-CURR'],
            [4, 2, 'ENG-MATDEV'],
            [4, 3, 'PROF-INT'],    [4, 3, 'LET-REV'],
        ];

        // Validate every code resolves to a subject row.
        $allCodes = collect($map)->pluck('2')->unique();
        $codeToId = DB::table('subjects')->whereIn('code', $allCodes)->pluck('id', 'code')->all();
        $missing = $allCodes->reject(fn ($c) => isset($codeToId[$c]));
        if ($missing->isNotEmpty()) {
            $this->command?->error('Missing subjects in DB after seed: ' . $missing->implode(', '));
            return;
        }

        // 4) Wipe and rebuild program_subjects for program 2.
        DB::table('program_subjects')->where('program_id', $programId)->delete();

        $now = now();
        $rows = [];
        foreach ($map as [$yl, $sem, $code]) {
            $rows[] = [
                'program_id' => $programId,
                'subject_id' => $codeToId[$code],
                'year_level' => $yl,
                'semester_number' => $sem,
                'is_active' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('program_subjects')->insert($rows);

        $this->command?->info(sprintf(
            'BSEDENG curriculum aligned: %d program_subjects rows.',
            count($rows)
        ));
    }
}
