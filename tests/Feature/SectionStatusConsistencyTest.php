<?php

namespace Tests\Feature;

use App\Http\Controllers\Staff\Registrar\StudentLedgerController;
use App\Models\School;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * sections.status is 'draft' | 'published' | 'archived'. The registrar ledger
 * import used to create sections as 'active' — a value the enrolment validator
 * treats as closed and the Sections page counted as neither draft nor
 * published. These tests pin the fixed behaviour: imports create published
 * sections, and the page's counter / publish-all sweep agree with the row
 * badge (anything not published/archived is a draft).
 */
class SectionStatusConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private int $termId;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var School $school */
        $school = School::factory()->create();
        $this->school = $school;

        $this->termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    }

    private function legacyActiveSection(string $name = 'BSED-Math 1A'): int
    {
        return DB::table('sections')->insertGetId([
            'school_id' => $this->school->id, 'term_id' => $this->termId,
            'name' => $name, 'status' => 'active', 'is_active' => 1,
        ]);
    }

    public function test_ledger_import_creates_sections_as_published(): void
    {
        $controller = new StudentLedgerController;
        $method = new \ReflectionMethod($controller, 'resolveSection');

        $sectionId = $method->invoke(
            $controller, 'Imported 1A', $this->school->id, Term::findOrFail($this->termId), 1, null
        );

        $this->assertDatabaseHas('sections', [
            'id' => $sectionId, 'name' => 'Imported 1A', 'status' => 'published', 'is_active' => 1,
        ]);
    }

    public function test_counter_counts_a_legacy_active_section_as_draft(): void
    {
        $this->legacyActiveSection();

        $this->actingAs($this->admin())
            ->get(route('admission.sections.index', ['term_id' => $this->termId]))
            ->assertOk()
            ->assertSeeText('1 draft');
    }

    public function test_publish_all_sweeps_legacy_active_sections(): void
    {
        $id = $this->legacyActiveSection();

        $this->actingAs($this->admin())
            ->post(route('admission.sections.publish-all'), ['term_id' => $this->termId])
            ->assertRedirect();

        $this->assertDatabaseHas('sections', ['id' => $id, 'status' => 'published', 'is_active' => 1]);
    }

    public function test_publish_all_does_not_touch_archived_or_published_sections(): void
    {
        $archived = DB::table('sections')->insertGetId([
            'school_id' => $this->school->id, 'term_id' => $this->termId,
            'name' => 'Old-4A', 'status' => 'archived', 'is_active' => 0,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admission.sections.publish-all'), ['term_id' => $this->termId])
            ->assertRedirect();

        $this->assertDatabaseHas('sections', ['id' => $archived, 'status' => 'archived']);
    }
}
