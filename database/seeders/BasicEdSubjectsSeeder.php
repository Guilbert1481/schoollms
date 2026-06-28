<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds DepEd Basic Education subjects for Memory Ridge (school_id = 1) only.
 *
 * Subjects are keyed by (school_id, code) so the seeder is idempotent.
 * Other schools are expected to encode their own subjects, or import via
 * the CSV upload in the dean curricula panel.
 */
class BasicEdSubjectsSeeder extends Seeder
{
    /** Target school: Memory Ridge International Schools and Colleges */
    protected int $schoolId = 1;

    public function run(): void
    {
        $groups = [
            // Kindergarten Learning Areas
            'KINDER' => [
                'Language Development',
                'Literacy and Communication',
                'Cognitive Development',
                'Physical and Motor Development',
                'Socio-Emotional Development',
                'Values Development',
                'Aesthetic and Creative Development',
            ],

            // Elementary - Key Stage 1
            'G1' => ['Language', 'Reading and Literacy', 'Mathematics', 'GMRC', 'Makabansa'],
            'G2' => ['Filipino', 'English', 'Mathematics', 'GMRC', 'Makabansa'],
            'G3' => ['Filipino', 'English', 'Mathematics', 'Science', 'GMRC', 'Makabansa'],

            // Elementary - Key Stage 2 (Grades 4-6) — same subjects, distinct per grade
            'G4' => [
                'Filipino', 'English', 'Mathematics', 'Science',
                'Araling Panlipunan', 'MAPEH', 'EPP/TLE',
                'GMRC / Values Education', 'Makabansa',
            ],
            'G5' => [
                'Filipino', 'English', 'Mathematics', 'Science',
                'Araling Panlipunan', 'MAPEH', 'EPP/TLE',
                'GMRC / Values Education', 'Makabansa',
            ],
            'G6' => [
                'Filipino', 'English', 'Mathematics', 'Science',
                'Araling Panlipunan', 'MAPEH', 'EPP/TLE',
                'GMRC / Values Education', 'Makabansa',
            ],

            // Junior High School — same subjects, distinct per grade
            'G7' => [
                'Filipino', 'English', 'Mathematics', 'Science',
                'Araling Panlipunan', 'MAPEH', 'Values Education',
                'TLE (Technology and Livelihood Education)',
            ],
            'G8' => [
                'Filipino', 'English', 'Mathematics', 'Science',
                'Araling Panlipunan', 'MAPEH', 'Values Education',
                'TLE (Technology and Livelihood Education)',
            ],
            'G9' => [
                'Filipino', 'English', 'Mathematics', 'Science',
                'Araling Panlipunan', 'MAPEH', 'Values Education',
                'TLE (Technology and Livelihood Education)',
            ],
            'G10' => [
                'Filipino', 'English', 'Mathematics', 'Science',
                'Araling Panlipunan', 'MAPEH', 'Values Education',
                'TLE (Technology and Livelihood Education)',
            ],

            // TLE Specializations (JHS)
            'TLE' => ['ICT', 'Home Economics', 'Industrial Arts', 'Agri-Fishery Arts'],

            // SHS Core — distinct per grade (G11/G12)
            'G11-CORE' => [
                'Oral Communication',
                'Reading and Writing',
                'Komunikasyon at Pananaliksik',
                '21st Century Literature',
                'Contemporary Philippine Arts',
                'Media and Information Literacy',
                'General Mathematics',
                'Statistics and Probability',
                'Earth and Life Science',
                'Physical Science',
                'Personal Development',
                'Understanding Culture, Society and Politics',
                'Physical Education and Health',
            ],
            'G12-CORE' => [
                'Oral Communication',
                'Reading and Writing',
                'Komunikasyon at Pananaliksik',
                '21st Century Literature',
                'Contemporary Philippine Arts',
                'Media and Information Literacy',
                'General Mathematics',
                'Statistics and Probability',
                'Earth and Life Science',
                'Physical Science',
                'Personal Development',
                'Understanding Culture, Society and Politics',
                'Physical Education and Health',
            ],

            // SHS Specialized — split per grade so each year has its own code
            'G11-STEM' => [
                'Pre-Calculus', 'Basic Calculus', 'General Biology',
                'General Physics', 'General Chemistry',
            ],
            'G12-STEM' => [
                'Pre-Calculus', 'Basic Calculus', 'General Biology',
                'General Physics', 'General Chemistry',
            ],
            'G11-ABM' => [
                'Business Math', 'Fundamentals of ABM',
                'Organization and Management', 'Applied Economics',
                'Business Finance',
            ],
            'G12-ABM' => [
                'Business Math', 'Fundamentals of ABM',
                'Organization and Management', 'Applied Economics',
                'Business Finance',
            ],
            'G11-HUMSS' => [
                'Creative Writing', 'Philippine Politics',
                'Community Engagement', 'Disciplines in Social Sciences',
                'Disciplines in Applied Social Sciences',
            ],
            'G12-HUMSS' => [
                'Creative Writing', 'Philippine Politics',
                'Community Engagement', 'Disciplines in Social Sciences',
                'Disciplines in Applied Social Sciences',
            ],

            // TVL Specializations — split per grade
            'G11-TVL' => [
                'ICT', 'Cookery', 'CSS', 'Automotive',
                'Electrical Installation', 'SMAW', 'Caregiving',
            ],
            'G12-TVL' => [
                'ICT', 'Cookery', 'CSS', 'Automotive',
                'Electrical Installation', 'SMAW', 'Caregiving',
            ],
        ];

        $now   = now();
        $rows  = [];
        $seen  = [];

        foreach ($groups as $prefix => $names) {
            foreach ($names as $name) {
                $code = $this->generateCode($prefix, $name);

                // Ensure uniqueness within the seeder batch
                $base = $code; $i = 2;
                while (isset($seen[$code])) {
                    $code = $base . '-' . $i++;
                }
                $seen[$code] = true;

                $rows[] = [
                    'school_id'   => $this->schoolId,
                    'code'        => $code,
                    'name'        => $name,
                    'description' => $this->describe($prefix, $name),
                    'is_active'   => 0,
                    'is_basic_ed' => 1,
                    'scope'       => 'academic',
                    'category'    => 'gen_ed',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        foreach ($rows as $row) {
            DB::table('subjects')->updateOrInsert(
                ['school_id' => $row['school_id'], 'code' => $row['code']],
                $row
            );
        }

        $this->command?->info('Seeded '.count($rows).' basic-ed subjects for school_id='.$this->schoolId.'.');
    }

    protected function generateCode(string $prefix, string $name): string
    {
        $slug = Str::of($name)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->value();

        if (strlen($slug) > 30) {
            $parts = explode('-', $slug);
            $slug  = strtoupper(implode('', array_map(fn ($p) => substr($p, 0, 3), $parts)));
            $slug  = substr($slug, 0, 30);
        }

        return substr($prefix.'-'.$slug, 0, 50);
    }

    protected function describe(string $prefix, string $name): string
    {
        $labels = [
            'KINDER'   => 'Kindergarten Learning Area',
            'G1'        => 'Grade 1 (MATATAG)',
            'G2'        => 'Grade 2 (MATATAG)',
            'G3'        => 'Grade 3 (MATATAG)',
            'G4'        => 'Grade 4 (MATATAG)',
            'G5'        => 'Grade 5 (MATATAG)',
            'G6'        => 'Grade 6 (MATATAG)',
            'G7'        => 'Grade 7 (Junior High School)',
            'G8'        => 'Grade 8 (Junior High School)',
            'G9'        => 'Grade 9 (Junior High School)',
            'G10'       => 'Grade 10 (Junior High School)',
            'TLE'       => 'JHS TLE Specialization',
            'G11-CORE'  => 'Grade 11 SHS Core Subject',
            'G12-CORE'  => 'Grade 12 SHS Core Subject',
            'G11-STEM'  => 'Grade 11 STEM Specialized Subject',
            'G12-STEM'  => 'Grade 12 STEM Specialized Subject',
            'G11-ABM'   => 'Grade 11 ABM Specialized Subject',
            'G12-ABM'   => 'Grade 12 ABM Specialized Subject',
            'G11-HUMSS' => 'Grade 11 HUMSS Specialized Subject',
            'G12-HUMSS' => 'Grade 12 HUMSS Specialized Subject',
            'G11-TVL'   => 'Grade 11 TVL Specialization',
            'G12-TVL'   => 'Grade 12 TVL Specialization',
        ];

        return ($labels[$prefix] ?? $prefix).' — '.$name;
    }
}
