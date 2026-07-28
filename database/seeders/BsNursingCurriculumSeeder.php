<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the CHED-aligned 4-year Bachelor of Science in Nursing (BSN) curriculum
 * (CMO No. 15, s. 2017), bi-semester, 192 units total, onto the existing BSN
 * program.
 *
 * REUSE BY NAME — not by code. Production already holds a full college subject
 * catalog (General Education, PE, NSTP) under auto-generated codes (e.g. Ethics
 * = GEN-ETHI-202). Creating GE rows under fresh codes would DUPLICATE them, so
 * every planned course is resolved against the existing catalog by name first
 * (scoped to college subjects, is_basic_ed = 0). Only genuinely-new courses —
 * the nursing science majors and the NCM professional series — are created.
 *
 * Electives reuse existing college electives (Gender and Society, Living in the
 * IT Era, Environmental Science) rather than inventing new ones; the CMO elective
 * slots are institutional choice, so swap the names in plan() if different
 * electives are preferred.
 *
 * Idempotent: subjects resolve by (school_id, name); curriculum/program links use
 * updateOrInsert. Safe to re-run. Targets the existing program by code 'BSN'
 * (found, not recreated) and never touches its dean / program-head assignment.
 *
 *     php artisan db:seed --class=BsNursingCurriculumSeeder
 */
class BsNursingCurriculumSeeder extends Seeder
{
    private const SCHOOL_ID = 1;

    private const COLLEGE_CODE = 'CON';

    private const COLLEGE_NAME = 'College of Nursing';

    private const PROGRAM_CODE = 'BSN';

    private const PROGRAM_NAME = 'Bachelor of Science in Nursing';

    private const CURRICULUM_NAME = 'BSN Curriculum 2017 (CMO 15 s. 2017)';

    private const VERSION = '2017';

    public function run(): void
    {
        $programId = $this->ensureProgram();
        $curriculumId = $this->ensureCurriculum($programId);

        $reused = 0;
        $created = 0;

        DB::transaction(function () use ($programId, $curriculumId, &$reused, &$created) {
            foreach ($this->plan() as $row) {
                $subjectId = $this->resolveOrCreateSubject($row, $reused, $created);

                DB::table('curriculum_subjects')->updateOrInsert(
                    ['curriculum_id' => $curriculumId, 'subject_id' => $subjectId],
                    [
                        'year_level' => $row['year'],
                        'semester' => (string) $row['sem'],
                        'units' => $row['units'],
                        'is_core' => ! ($row['elective'] ?? false),
                        'is_elective' => $row['elective'] ?? false,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                // Reachability: higher-ed subjects surface in the Course Architect
                // only through program_subjects. year_level / semester_number are
                // NOT NULL, so the CMO placement is written as an editable default.
                DB::table('program_subjects')->updateOrInsert(
                    ['program_id' => $programId, 'subject_id' => $subjectId],
                    [
                        'year_level' => $row['year'],
                        'semester_number' => (int) $row['sem'],
                        'is_active' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        $this->command?->info(sprintf(
            'Seeded BSN (CMO 15 s. 2017) onto program %d: %d curriculum entries (%d subjects reused, %d created).',
            $programId,
            DB::table('curriculum_subjects')->where('curriculum_id', $curriculumId)->count(),
            $reused,
            $created,
        ));
    }

    /* ================================================================== */
    /*  Setup helpers */
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

        // Fresh/test environments only — production already has the BSN program.
        $collegeId = DB::table('colleges')
            ->where('school_id', self::SCHOOL_ID)
            ->where('code', self::COLLEGE_CODE)
            ->value('id')
            ?? DB::table('colleges')->insertGetId([
                'school_id' => self::SCHOOL_ID,
                'name' => self::COLLEGE_NAME,
                'code' => self::COLLEGE_CODE,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return DB::table('programs')->insertGetId([
            'school_id' => self::SCHOOL_ID,
            'college_id' => $collegeId,
            'name' => self::PROGRAM_NAME,
            'code' => self::PROGRAM_CODE,
            'capacity' => 120,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function ensureCurriculum(int $programId): int
    {
        $existing = DB::table('curriculums')
            ->where('program_id', $programId)
            ->where('version', self::VERSION)
            ->value('id');

        if ($existing) {
            DB::table('curriculums')->where('id', $existing)->update([
                'is_active' => 1,
                'updated_at' => now(),
            ]);

            return $existing;
        }

        return DB::table('curriculums')->insertGetId([
            'school_id' => self::SCHOOL_ID,
            'program_id' => $programId,
            'name' => self::CURRICULUM_NAME,
            'version' => self::VERSION,
            'terms_per_year' => 2,
            'effective_from' => now()->startOfYear()->toDateString(),
            'is_active' => 1,
            'description' => 'CHED CMO 15 s. 2017 — 192-unit BSN program.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /* ================================================================== */
    /*  Subject resolution */
    /* ================================================================== */

    /**
     * Reuse an existing college subject with the same name, else create it.
     * Matching is scoped to college subjects (is_basic_ed = 0) so a college
     * course never collides with a same-named basic-ed learning area.
     */
    protected function resolveOrCreateSubject(array $row, int &$reused, int &$created): int
    {
        $existing = DB::table('subjects')
            ->where('school_id', self::SCHOOL_ID)
            ->where('is_basic_ed', 0)
            ->where('name', $row['name'])
            ->orderBy('id')
            ->value('id');

        if ($existing) {
            $reused++;

            return $existing;
        }

        $created++;

        return DB::table('subjects')->insertGetId([
            'school_id' => self::SCHOOL_ID,
            'code' => $row['code'],
            'name' => $row['name'],
            'units' => $row['units'],
            'category' => $row['category'],
            'scope' => 'academic',
            'is_active' => 1,
            'is_basic_ed' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /* ================================================================== */
    /*  Curriculum plan — CMO 15 s. 2017, Appendix B (bi-semester) */
    /*  Format: code, name, year, sem, units, category, [elective] */
    /*  Names are matched against the existing catalog; code/category are */
    /*  used only when a subject must be created (nursing majors, NCM). */
    /* ================================================================== */

    /** @return array<int, array<string, mixed>> */
    protected function plan(): array
    {
        return [
            // ---------------- YEAR 1, SEM 1 (27u) ----------------
            ['code' => 'GEN-US-101', 'name' => 'Understanding the Self', 'year' => 1, 'sem' => 1, 'units' => 3, 'category' => 'gen_ed'],
            ['code' => 'GEN-RPH-105', 'name' => 'Readings in Philippine History', 'year' => 1, 'sem' => 1, 'units' => 3, 'category' => 'gen_ed'],
            ['code' => 'NCM-100', 'name' => 'Theoretical Foundations of Nursing', 'year' => 1, 'sem' => 1, 'units' => 3, 'category' => 'prof_ed'],
            ['code' => 'NUR-ANAP', 'name' => 'Anatomy and Physiology', 'year' => 1, 'sem' => 1, 'units' => 5, 'category' => 'major'],
            ['code' => 'NUR-BIOCHE', 'name' => 'Biochemistry', 'year' => 1, 'sem' => 1, 'units' => 5, 'category' => 'major'],
            ['code' => 'GEN-MMW-102', 'name' => 'Mathematics in the Modern World', 'year' => 1, 'sem' => 1, 'units' => 3, 'category' => 'gen_ed'],
            ['code' => 'GEN-AA-201', 'name' => 'Art Appreciation', 'year' => 1, 'sem' => 1, 'units' => 3, 'category' => 'gen_ed'],
            ['code' => 'PE-PE-101', 'name' => 'Physical Education 1 (Movement Enhancement)', 'year' => 1, 'sem' => 1, 'units' => 2, 'category' => 'pe'],

            // ---------------- YEAR 1, SEM 2 (28u) ----------------
            ['code' => 'GEN-PC-103', 'name' => 'Purposive Communication', 'year' => 1, 'sem' => 2, 'units' => 3, 'category' => 'gen_ed'],
            ['code' => 'NCM-101', 'name' => 'Health Assessment', 'year' => 1, 'sem' => 2, 'units' => 5, 'category' => 'prof_ed'],
            ['code' => 'NCM-102', 'name' => 'Health Education', 'year' => 1, 'sem' => 2, 'units' => 3, 'category' => 'prof_ed'],
            ['code' => 'NCM-103', 'name' => 'Fundamentals of Nursing Practice', 'year' => 1, 'sem' => 2, 'units' => 5, 'category' => 'prof_ed'],
            ['code' => 'NUR-MICRO', 'name' => 'Microbiology and Parasitology', 'year' => 1, 'sem' => 2, 'units' => 4, 'category' => 'major'],
            ['code' => 'PE-PE-102', 'name' => 'Physical Education 2 (Fitness Exercises)', 'year' => 1, 'sem' => 2, 'units' => 2, 'category' => 'pe'],
            ['code' => 'GEN-ETHI-202', 'name' => 'Ethics', 'year' => 1, 'sem' => 2, 'units' => 3, 'category' => 'gen_ed'],
            ['code' => 'GEN-GS-302', 'name' => 'Gender and Society', 'year' => 1, 'sem' => 2, 'units' => 3, 'category' => 'gen_ed', 'elective' => true],

            // ---------------- YEAR 2, SEM 1 (27u) ----------------
            ['code' => 'NCM-104', 'name' => 'Community Health Nursing 1 (Individual and Family as Clients)', 'year' => 2, 'sem' => 1, 'units' => 4, 'category' => 'prof_ed'],
            ['code' => 'NCM-105', 'name' => 'Nutrition and Diet Therapy', 'year' => 2, 'sem' => 1, 'units' => 3, 'category' => 'prof_ed'],
            ['code' => 'NCM-106', 'name' => 'Pharmacology', 'year' => 2, 'sem' => 1, 'units' => 3, 'category' => 'prof_ed'],
            ['code' => 'NCM-107', 'name' => 'Care of Mother, Child, Adolescent (Well Clients)', 'year' => 2, 'sem' => 1, 'units' => 9, 'category' => 'prof_ed'],
            ['code' => 'NCM-108', 'name' => 'Health Care Ethics (Bioethics)', 'year' => 2, 'sem' => 1, 'units' => 3, 'category' => 'prof_ed'],
            ['code' => 'NSTP-NSTP-101', 'name' => 'NSTP 1', 'year' => 2, 'sem' => 1, 'units' => 3, 'category' => 'nstp'],
            ['code' => 'PE-PE-103', 'name' => 'Physical Education 3 (Individual/Dual Sports)', 'year' => 2, 'sem' => 1, 'units' => 2, 'category' => 'pe'],

            // ---------------- YEAR 2, SEM 2 (26u) ----------------
            ['code' => 'GEN-LIE-301', 'name' => 'Living in the IT Era', 'year' => 2, 'sem' => 2, 'units' => 3, 'category' => 'gen_ed', 'elective' => true],
            ['code' => 'NCM-109', 'name' => 'Care of Mother and Child at Risk or with Problems (Acute and Chronic)', 'year' => 2, 'sem' => 2, 'units' => 12, 'category' => 'prof_ed'],
            ['code' => 'NCM-110', 'name' => 'Nursing Informatics', 'year' => 2, 'sem' => 2, 'units' => 3, 'category' => 'prof_ed'],
            ['code' => 'GEN-STS-203', 'name' => 'Science, Technology and Society', 'year' => 2, 'sem' => 2, 'units' => 3, 'category' => 'gen_ed'],
            ['code' => 'NSTP-NSTP-102', 'name' => 'NSTP 2', 'year' => 2, 'sem' => 2, 'units' => 3, 'category' => 'nstp'],
            ['code' => 'PE-PE-201', 'name' => 'Physical Education 4 (Team Sports)', 'year' => 2, 'sem' => 2, 'units' => 2, 'category' => 'pe'],

            // ---------------- YEAR 3, SEM 1 (23u) ----------------
            ['code' => 'NCM-111', 'name' => 'Nursing Research 1', 'year' => 3, 'sem' => 1, 'units' => 3, 'category' => 'prof_ed'],
            ['code' => 'NCM-112', 'name' => 'Care of Clients w/ Problems in Oxygenation, Fluid & Electrolyte, Infectious, Inflammatory, Immunologic Response & Cellular Aberration', 'year' => 3, 'sem' => 1, 'units' => 14, 'category' => 'prof_ed'],
            ['code' => 'NCM-113', 'name' => 'Community Health Nursing 2 (Population Groups and Community as Clients)', 'year' => 3, 'sem' => 1, 'units' => 3, 'category' => 'prof_ed'],
            ['code' => 'NCM-114', 'name' => 'Care of the Older Adult', 'year' => 3, 'sem' => 1, 'units' => 3, 'category' => 'prof_ed'],

            // ---------------- YEAR 3, SEM 2 (22u) ----------------
            ['code' => 'NCM-115', 'name' => 'Nursing Research 2', 'year' => 3, 'sem' => 2, 'units' => 2, 'category' => 'prof_ed'],
            ['code' => 'NCM-116', 'name' => 'Care of Clients w/ Problems in Nutrition & GI, Metabolism & Endocrine, Perception & Coordination', 'year' => 3, 'sem' => 2, 'units' => 9, 'category' => 'prof_ed'],
            ['code' => 'NCM-117', 'name' => 'Care of Clients w/ Maladaptive Patterns of Behavior (Acute and Chronic)', 'year' => 3, 'sem' => 2, 'units' => 8, 'category' => 'prof_ed'],
            ['code' => 'NUR-LOGIC', 'name' => 'Logic and Critical Thinking', 'year' => 3, 'sem' => 2, 'units' => 3, 'category' => 'major'],

            // ---------------- YEAR 4, SEM 1 (25u) ----------------
            ['code' => 'NCM-118', 'name' => 'Nursing Care of Clients with Life-Threatening Conditions / High Acuity & Emergency Situations (Acute and Chronic)', 'year' => 4, 'sem' => 1, 'units' => 9, 'category' => 'prof_ed'],
            ['code' => 'NCM-119', 'name' => 'Nursing Leadership and Management', 'year' => 4, 'sem' => 1, 'units' => 7, 'category' => 'prof_ed'],
            ['code' => 'NCM-120', 'name' => 'Decent Work Employment and Transcultural Nursing', 'year' => 4, 'sem' => 1, 'units' => 3, 'category' => 'prof_ed'],
            ['code' => 'GEN-ES-108', 'name' => 'Environmental Science', 'year' => 4, 'sem' => 1, 'units' => 3, 'category' => 'gen_ed', 'elective' => true],
            ['code' => 'GEN-LWR-204', 'name' => 'Life and Works of Rizal', 'year' => 4, 'sem' => 1, 'units' => 3, 'category' => 'gen_ed'],

            // ---------------- YEAR 4, SEM 2 (14u) ----------------
            ['code' => 'NCM-121', 'name' => 'Disaster Nursing', 'year' => 4, 'sem' => 2, 'units' => 3, 'category' => 'prof_ed'],
            ['code' => 'NCM-122', 'name' => 'Intensive Nursing Practicum (Hospital and Community settings)', 'year' => 4, 'sem' => 2, 'units' => 8, 'category' => 'internship'],
            ['code' => 'GEN-CW-106', 'name' => 'The Contemporary World', 'year' => 4, 'sem' => 2, 'units' => 3, 'category' => 'gen_ed'],
        ];
    }
}
