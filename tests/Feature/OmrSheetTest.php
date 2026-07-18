<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Test;
use App\Models\User;
use App\Services\Tests\OmrSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 1 OMR: one printable answer sheet per enrolled student in a chosen
 * section, with the header + a unique signed QR. Covers roster generation,
 * the section picker source, and tenant isolation.
 */
class OmrSheetTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private int $academicYearId;

    private int $termId;

    private int $sectionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year' => '2026-2027', 'academic_year_id' => $this->academicYearId,
            'enrollment_type' => 'regular', 'term' => '1st Semester', 'name' => '1st Semester 2026-2027',
            'start_date' => '2026-06-01', 'end_date' => '2026-10-31',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->sectionId = DB::table('sections')->insertGetId([
            'school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'Rizal', 'year_level' => 8,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeTest(?int $schoolId = null): Test
    {
        $id = DB::table('tests')->insertGetId([
            'school_id' => $schoolId ?? $this->school->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'First Quarter Examination',
            'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Test::findOrFail($id);
    }

    private function enroll(string $last, string $first, string $number, ?int $sectionId = null): void
    {
        $studentId = DB::table('students')->insertGetId([
            'school_id' => $this->school->id, 'student_number' => $number,
            'first_name' => $first, 'last_name' => $last,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('student_enrollments')->insert([
            'school_id' => $this->school->id, 'student_id' => $studentId,
            'academic_year_id' => $this->academicYearId, 'term_id' => $this->termId,
            'section_id' => $sectionId ?? $this->sectionId, 'status' => 'enrolled',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_one_sheet_with_a_qr_per_enrolled_student(): void
    {
        $test = $this->makeTest();
        $this->enroll('Dela Cruz', 'Juan Miguel', '2026-00125');
        $this->enroll('Santos', 'Maria Clara', '2026-00126');

        $res = $this->actingAs($this->teacher)
            ->get(route('teacher.tests.answer-sheets', $test).'?section_id='.$this->sectionId)
            ->assertOk();

        $res->assertSee('Dela Cruz, Juan Miguel');
        $res->assertSee('Santos, Maria Clara');
        $res->assertSee('First Quarter Examination');
        $res->assertSee('Grade 8 – Rizal');

        // A signed QR token per student (two students → two data-qr attributes).
        $this->assertSame(2, substr_count($res->getContent(), 'data-qr='));
    }

    public function test_picker_lists_sections_with_enrolled_students(): void
    {
        $test = $this->makeTest();
        $this->enroll('Dela Cruz', 'Juan', '2026-00125');

        $sections = app(OmrSheetService::class)->sectionsForPicker($test);

        $this->assertCount(1, $sections);
        $this->assertSame($this->sectionId, (int) $sections->first()->id);
        $this->assertSame(1, (int) $sections->first()->student_count);
    }

    public function test_tenant_guard_blocks_a_foreign_school_test(): void
    {
        $other = School::factory()->create();
        $foreignTest = $this->makeTest($other->id);

        $this->actingAs($this->teacher)
            ->get(route('teacher.tests.answer-sheets', $foreignTest))
            ->assertNotFound();
    }

    public function test_tenant_guard_blocks_a_foreign_section(): void
    {
        $test = $this->makeTest();
        $other = School::factory()->create();
        $foreignSection = DB::table('sections')->insertGetId([
            'school_id' => $other->id, 'term_id' => $this->termId, 'name' => 'Foreign', 'year_level' => 8,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.tests.answer-sheets', $test).'?section_id='.$foreignSection)
            ->assertNotFound();
    }
}
