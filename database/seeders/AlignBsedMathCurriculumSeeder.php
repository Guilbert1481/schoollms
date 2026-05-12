<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Aligns the BSEDMATH (program_id=1) curriculum to the canonical 4-year, 3-term
 * subject map. Idempotent — safe to re-run.
 */
class AlignBsedMathCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $programId = 1;
        $schoolId = (int) (DB::table('programs')->where('id', $programId)->value('school_id') ?? 1);

        // 1) Subjects to ensure exist (code => [name, units])
        $needed = [
            'GEC-USELF'      => ['Understanding the Self', 3],
            'GEC-PURCO'      => ['Purposive Communication', 3],
            'FIL-1'          => ['Filipino sa Iba\'t Ibang Disiplina', 3],
            'PROF-CALL'      => ['The Child and Adolescent Learners', 3],
            'PE-1'           => ['Physical Education 1 (Movement Enhancement)', 2],
            'GEC-MMW'        => ['Mathematics in the Modern World', 3],
            'GEC-RPH'        => ['Readings in Philippine History', 3],
            'PROF-TP'        => ['The Teaching Profession', 3],
            'NSTP-1'         => ['NSTP 1', 3],
            'PE-2'           => ['Physical Education 2 (Fitness Exercises)', 2],
            'GEC-TCW'        => ['The Contemporary World', 3],
            'FIL-2'          => ['Pagbasa at Pagsulat sa Iba\'t Ibang Disiplina', 3],
            'MATH-ALG'       => ['College Algebra', 3],
            'NSTP-2'         => ['NSTP 2', 3],
            'PE-3'           => ['Physical Education 3 (Individual/Dual Sports)', 2],
            'GEC-ART'        => ['Art Appreciation', 3],
            'PROF-FSIE'      => ['Foundation of Special and Inclusive Education', 3],
            'MATH-GEO'       => ['Plane and Solid Geometry', 3],
            'MATH-TRIG'      => ['Trigonometry', 3],
            'PE-4'           => ['Physical Education 4 (Team Sports)', 2],
            'GEC-ETH'        => ['Ethics', 3],
            'PROF-FLCT'      => ['Facilitating Learner-Centered Teaching', 3],
            'MATH-CALC1'     => ['Calculus 1', 3],
            'MATH-LOGIC'     => ['Logic and Set Theory', 3],
            'GEC-STS'        => ['Science, Technology and Society', 3],
            'RIZAL'          => ['Life and Works of Rizal', 3],
            'PROF-TCSC'      => ['The Teacher and the Community, School Culture', 3],
            'PROF-TTL1'      => ['Technology for Teaching and Learning 1', 3],
            'PROF-TSC'       => ['The Teacher and the School Curriculum', 3],
            'MATH-CALC2'     => ['Calculus 2', 3],
            'MATH-LINALG'    => ['Linear Algebra', 3],
            'MATH-DIFFEQ'    => ['Differential Equations', 3],
            'MATH-DISCRETE'  => ['Discrete Mathematics', 3],
            'PROF-AL1'       => ['Assessment in Learning 1', 3],
            'PROF-RES1'      => ['Research 1: Methods of Research', 3],
            'MATH-ABALG'     => ['Abstract Algebra', 3],
            'MATH-PROBSTATS' => ['Probability and Statistics', 3],
            'MATH-NUMTH'     => ['Number Theory', 3],
            'PROF-NLAC'      => ['Building & Enhancing New Literacies', 3],
            'PROF-RES2'      => ['Research 2: Action Research', 3],
            'MATH-CALC3'     => ['Advanced Calculus (Calculus 3)', 3],
            'MATH-ANALYSIS'  => ['Mathematical Analysis', 3],
            'MATH-PROBSOLVE' => ['Problem Solving, Mathematical Investigation & Modeling', 3],
            'MATH-TTL2'      => ['Technology for Teaching & Learning 2 (Mathematics)', 3],
            'MATH-RES'       => ['Mathematics Research', 3],
            'FS-1'           => ['Field Study 1: Observation of Teaching', 3],
            'MATH-TMSS'      => ['Teaching Mathematics in Secondary Schools', 3],
            'MATH-ASSESS'    => ['Assessment of Learning in Mathematics', 3],
            'FS-2'           => ['Field Study 2: Participation & Teaching Assistance', 3],
            'MATH-HIST'      => ['History of Mathematics', 3],
            'MATH-CURR'      => ['Mathematics Curriculum and Instruction', 3],
            'MATH-MATDEV'    => ['Mathematics Teaching Materials Development', 3],
            'PROF-INT'       => ['Teaching Internship', 6],
            'LET-REV'        => ['LET Review', 3],
        ];

        foreach ($needed as $code => [$name, $units]) {
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

        // 2) Canonical map: [year_level, semester_number, code]
        $map = [
            // Year 1
            [1, 1, 'GEC-USELF'], [1, 1, 'GEC-PURCO'], [1, 1, 'FIL-1'],
            [1, 1, 'PROF-CALL'], [1, 1, 'PE-1'],
            [1, 2, 'GEC-MMW'],   [1, 2, 'GEC-RPH'],   [1, 2, 'PROF-TP'],
            [1, 2, 'NSTP-1'],    [1, 2, 'PE-2'],
            [1, 3, 'GEC-TCW'],   [1, 3, 'FIL-2'],     [1, 3, 'MATH-ALG'],
            [1, 3, 'NSTP-2'],    [1, 3, 'PE-3'],
            // Year 2
            [2, 1, 'GEC-ART'],   [2, 1, 'PROF-FSIE'], [2, 1, 'MATH-GEO'],
            [2, 1, 'MATH-TRIG'], [2, 1, 'PE-4'],
            [2, 2, 'GEC-ETH'],   [2, 2, 'PROF-FLCT'], [2, 2, 'MATH-CALC1'],
            [2, 2, 'MATH-LOGIC'],[2, 2, 'GEC-STS'],
            [2, 3, 'RIZAL'],     [2, 3, 'PROF-TCSC'], [2, 3, 'PROF-TTL1'],
            [2, 3, 'PROF-TSC'],  [2, 3, 'MATH-CALC2'],
            // Year 3
            [3, 1, 'MATH-LINALG'],   [3, 1, 'MATH-DIFFEQ'],   [3, 1, 'MATH-DISCRETE'],
            [3, 1, 'PROF-AL1'],      [3, 1, 'PROF-RES1'],
            [3, 2, 'MATH-ABALG'],    [3, 2, 'MATH-PROBSTATS'],[3, 2, 'MATH-NUMTH'],
            [3, 2, 'PROF-NLAC'],     [3, 2, 'PROF-RES2'],
            [3, 3, 'MATH-CALC3'],    [3, 3, 'MATH-ANALYSIS'], [3, 3, 'MATH-PROBSOLVE'],
            [3, 3, 'MATH-TTL2'],     [3, 3, 'MATH-RES'],
            // Year 4
            [4, 1, 'FS-1'],          [4, 1, 'MATH-TMSS'],     [4, 1, 'MATH-ASSESS'],
            [4, 2, 'FS-2'],          [4, 2, 'MATH-HIST'],     [4, 2, 'MATH-CURR'],
            [4, 2, 'MATH-MATDEV'],
            [4, 3, 'PROF-INT'],      [4, 3, 'LET-REV'],
        ];

        $allCodes = collect($map)->pluck('2')->unique();
        $codeToId = DB::table('subjects')->whereIn('code', $allCodes)->pluck('id', 'code')->all();
        $missing = $allCodes->reject(fn ($c) => isset($codeToId[$c]));
        if ($missing->isNotEmpty()) {
            $this->command?->error('Missing subjects after seed: ' . $missing->implode(', '));
            return;
        }

        // 3) Wipe and rebuild program_subjects for program 1.
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
            'BSEDMATH curriculum aligned: %d program_subjects rows.',
            count($rows)
        ));
    }
}
