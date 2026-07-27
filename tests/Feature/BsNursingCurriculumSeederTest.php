<?php

namespace Tests\Feature;

use App\Models\School;
use Database\Seeders\BsNursingCurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The BSN seeder must reuse subjects that already exist in the college catalog
 * (General Education, PE, NSTP) rather than re-create them, and create only the
 * nursing-specific courses. These tests reproduce the production situation —
 * GE subjects already present under their own codes — and pin that behaviour.
 */
class BsNursingCurriculumSeederTest extends TestCase
{
    use RefreshDatabase;

    private const TOTAL_UNITS = 192;

    private const PLAN_ROWS = 45;

    /** Shared subjects the plan reuses, keyed by the exact name it matches on. */
    private const SHARED = [
        'Understanding the Self' => 'GEN-US-101',
        'Readings in Philippine History' => 'GEN-RPH-105',
        'The Contemporary World' => 'GEN-CW-106',
        'Mathematics in the Modern World' => 'GEN-MMW-102',
        'Purposive Communication' => 'GEN-PC-103',
        'Art Appreciation' => 'GEN-AA-201',
        'Science, Technology and Society' => 'GEN-STS-203',
        'Ethics' => 'GEN-ETHI-202',
        'Life and Works of Rizal' => 'GEN-LWR-204',
        'Gender and Society' => 'GEN-GS-302',
        'Living in the IT Era' => 'GEN-LIE-301',
        'Environmental Science' => 'GEN-ES-108',
        'Physical Education 1 (Movement Enhancement)' => 'PE-PE-101',
        'Physical Education 2 (Fitness Exercises)' => 'PE-PE-102',
        'Physical Education 3 (Individual/Dual Sports)' => 'PE-PE-103',
        'Physical Education 4 (Team Sports)' => 'PE-PE-201',
        'NSTP 1' => 'NSTP-NSTP-101',
        'NSTP 2' => 'NSTP-NSTP-102',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (! School::find(1)) {
            School::factory()->create(['id' => 1]);
        }
    }

    /** Insert the shared catalog the way production already holds it. */
    private function seedExistingCatalog(): array
    {
        $ids = [];
        foreach (self::SHARED as $name => $code) {
            $ids[$name] = DB::table('subjects')->insertGetId([
                'school_id' => 1,
                'code' => $code,
                'name' => $name,
                'is_basic_ed' => 0,
                'scope' => 'academic',
                'category' => str_starts_with($code, 'PE') ? 'pe' : (str_starts_with($code, 'NSTP') ? 'nstp' : 'gen_ed'),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    public function test_it_reuses_existing_subjects_and_creates_only_nursing_courses(): void
    {
        $existingIds = $this->seedExistingCatalog();
        $this->assertSame(18, DB::table('subjects')->where('school_id', 1)->count());

        (new BsNursingCurriculumSeeder)->setContainer($this->app)->run();

        // 18 pre-existing + 27 newly created nursing courses = 45. If any shared
        // subject had been duplicated this count would exceed 45.
        $this->assertSame(45, DB::table('subjects')->where('school_id', 1)->count());

        // Every shared subject still appears exactly once, under its original id.
        foreach ($existingIds as $name => $id) {
            $this->assertSame(
                1,
                DB::table('subjects')->where('school_id', 1)->where('name', $name)->count(),
                "Shared subject '{$name}' must not be duplicated."
            );

            $subjectId = DB::table('curriculum_subjects as cs')
                ->join('subjects as s', 's.id', '=', 'cs.subject_id')
                ->where('s.name', $name)
                ->value('cs.subject_id');

            $this->assertSame($id, $subjectId, "Plan must reference the pre-existing '{$name}' row.");
        }
    }

    public function test_it_builds_the_full_192_unit_curriculum(): void
    {
        (new BsNursingCurriculumSeeder)->setContainer($this->app)->run();

        $curriculumId = DB::table('curriculums')->where('version', '2017')->value('id');
        $programId = DB::table('programs')->where('code', 'BSN')->value('id');

        $this->assertNotNull($curriculumId);
        $this->assertEquals(self::TOTAL_UNITS, (int) DB::table('curriculum_subjects')->where('curriculum_id', $curriculumId)->sum('units'));
        $this->assertSame(self::PLAN_ROWS, DB::table('curriculum_subjects')->where('curriculum_id', $curriculumId)->count());
        $this->assertSame(self::PLAN_ROWS, DB::table('program_subjects')->where('program_id', $programId)->count());
    }

    public function test_it_attaches_to_an_existing_bsn_program_without_duplicating_it(): void
    {
        $collegeId = DB::table('colleges')->insertGetId([
            'school_id' => 1, 'code' => 'CON', 'name' => 'College of Nursing',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $programId = DB::table('programs')->insertGetId([
            'school_id' => 1, 'college_id' => $collegeId, 'code' => 'BSN',
            'name' => 'Bachelor of Science in Nursing', 'capacity' => 120, 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        (new BsNursingCurriculumSeeder)->setContainer($this->app)->run();

        // Still one BSN program, and the subjects landed on the existing one.
        $this->assertSame(1, DB::table('programs')->where('school_id', 1)->where('code', 'BSN')->count());
        $this->assertSame(45, DB::table('program_subjects')->where('program_id', $programId)->count());
    }

    public function test_re_running_is_idempotent(): void
    {
        $seeder = (new BsNursingCurriculumSeeder)->setContainer($this->app);
        $seeder->run();
        $seeder->run();

        $curriculumId = DB::table('curriculums')->where('version', '2017')->value('id');
        $this->assertSame(self::PLAN_ROWS, DB::table('curriculum_subjects')->where('curriculum_id', $curriculumId)->count());
        $this->assertSame(45, DB::table('subjects')->where('school_id', 1)->count());
    }
}
