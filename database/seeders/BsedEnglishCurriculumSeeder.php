<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a complete 4-year BSEd-English curriculum.
 *
 * The "button" the dean asked for is right here: the seeder respects the
 * Curriculum->terms_per_year flag (2 = bi-sem, 3 = tri-sem). You can also
 * override it at runtime:
 *
 *     php artisan db:seed --class=BsedEnglishCurriculumSeeder
 *     TERMS_PER_YEAR=3 php artisan db:seed --class=BsedEnglishCurriculumSeeder
 *
 * Idempotent: safe to re-run; updates existing rows instead of duplicating.
 */
class BsedEnglishCurriculumSeeder extends Seeder
{
    private const SCHOOL_ID       = 1;
    private const PROGRAM_CODE    = 'BSED-ENG';
    private const PROGRAM_NAME    = 'Bachelor of Secondary Education major in English';
    private const CURRICULUM_NAME = 'BSEd English Curriculum 2024';
    private const VERSION         = '2024';

    public function run(): void
    {
        $termsPerYear = (int) (env('TERMS_PER_YEAR', 2));
        if (! in_array($termsPerYear, [2, 3], true)) {
            $this->command?->warn("TERMS_PER_YEAR must be 2 or 3. Falling back to 2.");
            $termsPerYear = 2;
        }

        $programId    = $this->ensureProgram();
        $curriculumId = $this->ensureCurriculum($programId, $termsPerYear);

        $plan = $termsPerYear === 3 ? $this->triSemPlan() : $this->biSemPlan();

        DB::transaction(function () use ($curriculumId, $plan) {
            foreach ($plan as $row) {
                $subjectId = $this->ensureSubject($row['code'], $row['name']);

                DB::table('curriculum_subjects')->updateOrInsert(
                    [
                        'curriculum_id' => $curriculumId,
                        'subject_id'    => $subjectId,
                    ],
                    [
                        'year_level'  => $row['year'],
                        'semester'    => (string) $row['sem'],
                        'units'       => $row['units'],
                        'is_core'     => $row['core']     ?? true,
                        'is_elective' => $row['elective'] ?? false,
                        'updated_at'  => now(),
                        'created_at'  => now(),
                    ]
                );
            }
        });

        $this->command?->info(sprintf(
            "Seeded BSEd-English (%d terms/year): %d subjects across %d curriculum entries.",
            $termsPerYear,
            DB::table('subjects')->where('school_id', self::SCHOOL_ID)->count(),
            DB::table('curriculum_subjects')->where('curriculum_id', $curriculumId)->count(),
        ));
    }

    /* ================================================================== */
    /*  Setup helpers                                                     */
    /* ================================================================== */

    protected function ensureProgram(): int
    {
        $existing = DB::table('programs')
            ->where('school_id', self::SCHOOL_ID)
            ->where('code', self::PROGRAM_CODE)
            ->value('id');

        if ($existing) {
            return $existing;
        }

        // programs.college_id is NOT NULL — make sure one exists.
        $collegeId = DB::table('colleges')
            ->where('school_id', self::SCHOOL_ID)
            ->value('id');

        if (! $collegeId) {
            $collegeId = DB::table('colleges')->insertGetId([
                'school_id'  => self::SCHOOL_ID,
                'name'       => 'College of Education',
                'code'       => 'COE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return DB::table('programs')->insertGetId([
            'school_id'  => self::SCHOOL_ID,
            'college_id' => $collegeId,
            'name'       => self::PROGRAM_NAME,
            'code'       => self::PROGRAM_CODE,
            'capacity'   => 120,
            'active'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function ensureCurriculum(int $programId, int $termsPerYear): int
    {
        $existing = DB::table('curriculums')
            ->where('program_id', $programId)
            ->where('version', self::VERSION)
            ->first();

        if ($existing) {
            DB::table('curriculums')->where('id', $existing->id)->update([
                'terms_per_year' => $termsPerYear,
                'is_active'      => 1,
                'updated_at'     => now(),
            ]);
            return $existing->id;
        }

        return DB::table('curriculums')->insertGetId([
            'school_id'      => self::SCHOOL_ID,
            'program_id'     => $programId,
            'name'           => self::CURRICULUM_NAME,
            'version'        => self::VERSION,
            'terms_per_year' => $termsPerYear,
            'effective_from' => now()->startOfYear()->toDateString(),
            'is_active'      => 1,
            'description'    => 'CMO-aligned BSEd English curriculum.',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    protected function ensureSubject(string $code, string $name): int
    {
        $existing = DB::table('subjects')
            ->where('school_id', self::SCHOOL_ID)
            ->where('code', $code)
            ->value('id');

        if ($existing) {
            return $existing;
        }

        return DB::table('subjects')->insertGetId([
            'school_id'  => self::SCHOOL_ID,
            'code'       => $code,
            'name'       => $name,
            'is_active'  => 1,
            'scope'      => 'academic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /* ================================================================== */
    /*  Curriculum plans                                                  */
    /*  Format: ['code','name','year','sem','units','core','elective']    */
    /* ================================================================== */

    protected function biSemPlan(): array
    {
        return [
            // -------- YEAR 1, SEMESTER 1 (20 u) --------
            ['code' => 'GEC-USELF', 'name' => 'Understanding the Self',                        'year' => 1, 'sem' => 1, 'units' => 3],
            ['code' => 'GEC-MMW',   'name' => 'Mathematics in the Modern World',               'year' => 1, 'sem' => 1, 'units' => 3],
            ['code' => 'GEC-PURCO', 'name' => 'Purposive Communication',                       'year' => 1, 'sem' => 1, 'units' => 3],
            ['code' => 'FIL-1',     'name' => 'Filipino sa Iba\'t Ibang Disiplina',            'year' => 1, 'sem' => 1, 'units' => 3],
            ['code' => 'PROF-CALL', 'name' => 'The Child and Adolescent Learners',             'year' => 1, 'sem' => 1, 'units' => 3],
            ['code' => 'PE-1',      'name' => 'Physical Education 1 (Movement Enhancement)',   'year' => 1, 'sem' => 1, 'units' => 2],
            ['code' => 'NSTP-1',    'name' => 'NSTP 1',                                        'year' => 1, 'sem' => 1, 'units' => 3],

            // -------- YEAR 1, SEMESTER 2 (20 u) --------
            ['code' => 'GEC-RPH',   'name' => 'Readings in Philippine History',                'year' => 1, 'sem' => 2, 'units' => 3],
            ['code' => 'GEC-TCW',   'name' => 'The Contemporary World',                        'year' => 1, 'sem' => 2, 'units' => 3],
            ['code' => 'FIL-2',     'name' => 'Pagbasa at Pagsulat sa Iba\'t Ibang Disiplina', 'year' => 1, 'sem' => 2, 'units' => 3],
            ['code' => 'PROF-TP',   'name' => 'The Teaching Profession',                       'year' => 1, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-LING',  'name' => 'Introduction to Linguistics',                   'year' => 1, 'sem' => 2, 'units' => 3],
            ['code' => 'PE-2',      'name' => 'Physical Education 2 (Fitness Exercises)',      'year' => 1, 'sem' => 2, 'units' => 2],
            ['code' => 'NSTP-2',    'name' => 'NSTP 2',                                        'year' => 1, 'sem' => 2, 'units' => 3],

            // -------- YEAR 2, SEMESTER 1 (20 u) --------
            ['code' => 'GEC-ART',   'name' => 'Art Appreciation',                              'year' => 2, 'sem' => 1, 'units' => 3],
            ['code' => 'GEC-ETH',   'name' => 'Ethics',                                        'year' => 2, 'sem' => 1, 'units' => 3],
            ['code' => 'PROF-FSIE', 'name' => 'Foundation of Special and Inclusive Education', 'year' => 2, 'sem' => 1, 'units' => 3],
            ['code' => 'PROF-FLCT', 'name' => 'Facilitating Learner-Centered Teaching',        'year' => 2, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-STRC',  'name' => 'Structure of English',                          'year' => 2, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-SEAL',  'name' => 'Survey of English and American Literature',    'year' => 2, 'sem' => 1, 'units' => 3],
            ['code' => 'PE-3',      'name' => 'Physical Education 3 (Individual/Dual Sports)','year' => 2, 'sem' => 1, 'units' => 2],

            // -------- YEAR 2, SEMESTER 2 (20 u) --------
            ['code' => 'GEC-STS',   'name' => 'Science, Technology and Society',               'year' => 2, 'sem' => 2, 'units' => 3],
            ['code' => 'RIZAL',     'name' => 'Life and Works of Rizal',                       'year' => 2, 'sem' => 2, 'units' => 3],
            ['code' => 'PROF-TCSC', 'name' => 'The Teacher and the Community, School Culture', 'year' => 2, 'sem' => 2, 'units' => 3],
            ['code' => 'PROF-TTL1', 'name' => 'Technology for Teaching and Learning 1',        'year' => 2, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-LCS',   'name' => 'Language, Culture and Society',                 'year' => 2, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-MYTH',  'name' => 'Mythology and Folklore',                        'year' => 2, 'sem' => 2, 'units' => 3],
            ['code' => 'PE-4',      'name' => 'Physical Education 4 (Team Sports)',            'year' => 2, 'sem' => 2, 'units' => 2],

            // -------- YEAR 3, SEMESTER 1 (18 u) --------
            ['code' => 'PROF-TSC',  'name' => 'The Teacher and the School Curriculum',         'year' => 3, 'sem' => 1, 'units' => 3],
            ['code' => 'PROF-AL1',  'name' => 'Assessment in Learning 1',                      'year' => 3, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-PTLA',  'name' => 'Principles & Theories of Language Acquisition', 'year' => 3, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-SPLE',  'name' => 'Survey of Philippine Literature in English',    'year' => 3, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-MACRO', 'name' => 'Teaching and Assessment of Macroskills',        'year' => 3, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-SPCH',  'name' => 'Speech and Theater Arts',                       'year' => 3, 'sem' => 1, 'units' => 3],

            // -------- YEAR 3, SEMESTER 2 (21 u) --------
            ['code' => 'PROF-NLAC', 'name' => 'Building & Enhancing New Literacies',           'year' => 3, 'sem' => 2, 'units' => 3],
            ['code' => 'PROF-AL2',  'name' => 'Assessment in Learning 2',                      'year' => 3, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-GRAM',  'name' => 'Teaching and Assessment of the Grammar',        'year' => 3, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-AFRO',  'name' => 'Survey of Afro-Asian Literature',               'year' => 3, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-CHILD', 'name' => 'Children and Adolescent Literature',            'year' => 3, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-TTL2',  'name' => 'Technology for Teaching & Learning 2 (English)','year' => 3, 'sem' => 2, 'units' => 3],
            ['code' => 'GE-LITERA', 'name' => 'Living in the IT Era',                          'year' => 3, 'sem' => 2, 'units' => 3, 'core' => false, 'elective' => true],

            // -------- YEAR 4, SEMESTER 1 (21 u) --------
            ['code' => 'FS-1',      'name' => 'Field Study 1: Observation of Teaching',        'year' => 4, 'sem' => 1, 'units' => 3],
            ['code' => 'PROF-RES1', 'name' => 'Research 1: Methods of Research',               'year' => 4, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-LIT',   'name' => 'Teaching and Assessment of Literature Studies', 'year' => 4, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-CONTP', 'name' => 'Contemporary, Popular and Emergent Literature', 'year' => 4, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-STYL',  'name' => 'Stylistics and Discourse Analysis',             'year' => 4, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-CAMPJ', 'name' => 'Campus Journalism',                             'year' => 4, 'sem' => 1, 'units' => 3],
            ['code' => 'GE-GENDR',  'name' => 'Gender and Society',                            'year' => 4, 'sem' => 1, 'units' => 3, 'core' => false, 'elective' => true],

            // -------- YEAR 4, SEMESTER 2 (24 u — internship-heavy) --------
            ['code' => 'FS-2',      'name' => 'Field Study 2: Participation & Teaching Asst.', 'year' => 4, 'sem' => 2, 'units' => 3],
            ['code' => 'PROF-RES2', 'name' => 'Research 2: Action Research',                   'year' => 4, 'sem' => 2, 'units' => 3],
            ['code' => 'PROF-INT',  'name' => 'Teaching Internship',                           'year' => 4, 'sem' => 2, 'units' => 6],
            ['code' => 'ENG-LLMD',  'name' => 'Language Learning Materials Development',       'year' => 4, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-LER',   'name' => 'Language Education Research',                   'year' => 4, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-TWET',  'name' => 'Technical Writing for English Teachers',        'year' => 4, 'sem' => 2, 'units' => 3],
            ['code' => 'GE-ENVSC',  'name' => 'Environmental Science',                         'year' => 4, 'sem' => 2, 'units' => 3, 'core' => false, 'elective' => true],
        ];
    }

    /**
     * Tri-sem plan: same 50 subjects, redistributed into 12 terms (~7 u/term).
     * Same year_level, but semester now uses values 1, 2, 3.
     */
    protected function triSemPlan(): array
    {
        return [
            // -------- YEAR 1, TERM 1 (~13 u) --------
            ['code' => 'GEC-USELF', 'name' => 'Understanding the Self',                        'year' => 1, 'sem' => 1, 'units' => 3],
            ['code' => 'GEC-PURCO', 'name' => 'Purposive Communication',                       'year' => 1, 'sem' => 1, 'units' => 3],
            ['code' => 'FIL-1',     'name' => 'Filipino sa Iba\'t Ibang Disiplina',            'year' => 1, 'sem' => 1, 'units' => 3],
            ['code' => 'PROF-CALL', 'name' => 'The Child and Adolescent Learners',             'year' => 1, 'sem' => 1, 'units' => 3],
            ['code' => 'PE-1',      'name' => 'Physical Education 1 (Movement Enhancement)',   'year' => 1, 'sem' => 1, 'units' => 2],

            // -------- YEAR 1, TERM 2 (~14 u) --------
            ['code' => 'GEC-MMW',   'name' => 'Mathematics in the Modern World',               'year' => 1, 'sem' => 2, 'units' => 3],
            ['code' => 'GEC-RPH',   'name' => 'Readings in Philippine History',                'year' => 1, 'sem' => 2, 'units' => 3],
            ['code' => 'PROF-TP',   'name' => 'The Teaching Profession',                       'year' => 1, 'sem' => 2, 'units' => 3],
            ['code' => 'NSTP-1',    'name' => 'NSTP 1',                                        'year' => 1, 'sem' => 2, 'units' => 3],
            ['code' => 'PE-2',      'name' => 'Physical Education 2 (Fitness Exercises)',      'year' => 1, 'sem' => 2, 'units' => 2],

            // -------- YEAR 1, TERM 3 (~14 u) --------
            ['code' => 'GEC-TCW',   'name' => 'The Contemporary World',                        'year' => 1, 'sem' => 3, 'units' => 3],
            ['code' => 'FIL-2',     'name' => 'Pagbasa at Pagsulat sa Iba\'t Ibang Disiplina', 'year' => 1, 'sem' => 3, 'units' => 3],
            ['code' => 'ENG-LING',  'name' => 'Introduction to Linguistics',                   'year' => 1, 'sem' => 3, 'units' => 3],
            ['code' => 'NSTP-2',    'name' => 'NSTP 2',                                        'year' => 1, 'sem' => 3, 'units' => 3],
            ['code' => 'PE-3',      'name' => 'Physical Education 3 (Individual/Dual Sports)','year' => 1, 'sem' => 3, 'units' => 2],

            // -------- YEAR 2, TERM 1 (~14 u) --------
            ['code' => 'GEC-ART',   'name' => 'Art Appreciation',                              'year' => 2, 'sem' => 1, 'units' => 3],
            ['code' => 'PROF-FSIE', 'name' => 'Foundation of Special and Inclusive Education', 'year' => 2, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-STRC',  'name' => 'Structure of English',                          'year' => 2, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-SEAL',  'name' => 'Survey of English and American Literature',    'year' => 2, 'sem' => 1, 'units' => 3],
            ['code' => 'PE-4',      'name' => 'Physical Education 4 (Team Sports)',            'year' => 2, 'sem' => 1, 'units' => 2],

            // -------- YEAR 2, TERM 2 (~12 u) --------
            ['code' => 'GEC-ETH',   'name' => 'Ethics',                                        'year' => 2, 'sem' => 2, 'units' => 3],
            ['code' => 'PROF-FLCT', 'name' => 'Facilitating Learner-Centered Teaching',        'year' => 2, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-LCS',   'name' => 'Language, Culture and Society',                 'year' => 2, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-MYTH',  'name' => 'Mythology and Folklore',                        'year' => 2, 'sem' => 2, 'units' => 3],

            // -------- YEAR 2, TERM 3 (~12 u) --------
            ['code' => 'GEC-STS',   'name' => 'Science, Technology and Society',               'year' => 2, 'sem' => 3, 'units' => 3],
            ['code' => 'RIZAL',     'name' => 'Life and Works of Rizal',                       'year' => 2, 'sem' => 3, 'units' => 3],
            ['code' => 'PROF-TCSC', 'name' => 'The Teacher and the Community, School Culture', 'year' => 2, 'sem' => 3, 'units' => 3],
            ['code' => 'PROF-TTL1', 'name' => 'Technology for Teaching and Learning 1',        'year' => 2, 'sem' => 3, 'units' => 3],

            // -------- YEAR 3, TERM 1 (~12 u) --------
            ['code' => 'PROF-TSC',  'name' => 'The Teacher and the School Curriculum',         'year' => 3, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-PTLA',  'name' => 'Principles & Theories of Language Acquisition', 'year' => 3, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-SPLE',  'name' => 'Survey of Philippine Literature in English',    'year' => 3, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-SPCH',  'name' => 'Speech and Theater Arts',                       'year' => 3, 'sem' => 1, 'units' => 3],

            // -------- YEAR 3, TERM 2 (~12 u) --------
            ['code' => 'PROF-AL1',  'name' => 'Assessment in Learning 1',                      'year' => 3, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-MACRO', 'name' => 'Teaching and Assessment of Macroskills',        'year' => 3, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-AFRO',  'name' => 'Survey of Afro-Asian Literature',               'year' => 3, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-CHILD', 'name' => 'Children and Adolescent Literature',            'year' => 3, 'sem' => 2, 'units' => 3],

            // -------- YEAR 3, TERM 3 (~15 u) --------
            ['code' => 'PROF-NLAC', 'name' => 'Building & Enhancing New Literacies',           'year' => 3, 'sem' => 3, 'units' => 3],
            ['code' => 'PROF-AL2',  'name' => 'Assessment in Learning 2',                      'year' => 3, 'sem' => 3, 'units' => 3],
            ['code' => 'ENG-GRAM',  'name' => 'Teaching and Assessment of the Grammar',        'year' => 3, 'sem' => 3, 'units' => 3],
            ['code' => 'ENG-TTL2',  'name' => 'Technology for Teaching & Learning 2 (English)','year' => 3, 'sem' => 3, 'units' => 3],
            ['code' => 'GE-LITERA', 'name' => 'Living in the IT Era',                          'year' => 3, 'sem' => 3, 'units' => 3, 'core' => false, 'elective' => true],

            // -------- YEAR 4, TERM 1 (~15 u) --------
            ['code' => 'FS-1',      'name' => 'Field Study 1: Observation of Teaching',        'year' => 4, 'sem' => 1, 'units' => 3],
            ['code' => 'PROF-RES1', 'name' => 'Research 1: Methods of Research',               'year' => 4, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-LIT',   'name' => 'Teaching and Assessment of Literature Studies', 'year' => 4, 'sem' => 1, 'units' => 3],
            ['code' => 'ENG-CONTP', 'name' => 'Contemporary, Popular and Emergent Literature', 'year' => 4, 'sem' => 1, 'units' => 3],
            ['code' => 'GE-GENDR',  'name' => 'Gender and Society',                            'year' => 4, 'sem' => 1, 'units' => 3, 'core' => false, 'elective' => true],

            // -------- YEAR 4, TERM 2 (~12 u) --------
            ['code' => 'FS-2',      'name' => 'Field Study 2: Participation & Teaching Asst.', 'year' => 4, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-STYL',  'name' => 'Stylistics and Discourse Analysis',             'year' => 4, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-CAMPJ', 'name' => 'Campus Journalism',                             'year' => 4, 'sem' => 2, 'units' => 3],
            ['code' => 'ENG-LLMD',  'name' => 'Language Learning Materials Development',       'year' => 4, 'sem' => 2, 'units' => 3],

            // -------- YEAR 4, TERM 3 (~15 u — internship term) --------
            ['code' => 'PROF-INT',  'name' => 'Teaching Internship',                           'year' => 4, 'sem' => 3, 'units' => 6],
            ['code' => 'PROF-RES2', 'name' => 'Research 2: Action Research',                   'year' => 4, 'sem' => 3, 'units' => 3],
            ['code' => 'ENG-LER',   'name' => 'Language Education Research',                   'year' => 4, 'sem' => 3, 'units' => 3],
            ['code' => 'ENG-TWET',  'name' => 'Technical Writing for English Teachers',        'year' => 4, 'sem' => 3, 'units' => 3],
            ['code' => 'GE-ENVSC',  'name' => 'Environmental Science',                         'year' => 4, 'sem' => 3, 'units' => 3, 'core' => false, 'elective' => true],
        ];
    }
}
