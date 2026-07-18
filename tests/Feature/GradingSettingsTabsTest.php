<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\School;
use App\Models\User;
use App\Support\EducationLevels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Principal's Grading Scheme page folds its long basic-ed grade list behind
 * the shared education-level tabs (offered stage groups). Because academic_levels
 * carry no education_node FK, levels are bridged into stage groups by name — the
 * awkward case being "Kinder" (academic level) vs "Kindergarten" (tree node).
 */
class GradingSettingsTabsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $principal;

    private int $preschoolId;

    private int $elementaryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        $this->principal = User::factory()->create(['school_id' => $this->school->id, 'role' => 'principal']);

        // Minimal offered Basic-Ed tree: two stage groups so the tabs render.
        $root = $this->node('Basic Education', null, 'level', 0);
        $this->preschoolId = $this->node('Preschool', $root, 'stage', 0);
        $this->node('Kindergarten', $this->preschoolId, 'stage', 0);
        $this->elementaryId = $this->node('Elementary', $root, 'stage', 1);
        $this->node('Grade 1', $this->elementaryId, 'stage', 0);
        $this->node('Grade 2', $this->elementaryId, 'stage', 1);

        // The school's basic academic levels (what grading settings key off).
        $this->level('Kinder', 0);
        $this->level('Grade 1', 1);
        $this->level('Grade 2', 2);
    }

    private function node(string $name, ?int $parentId, string $type, int $order): int
    {
        return DB::table('education_nodes')->insertGetId([
            'name' => $name, 'parent_id' => $parentId, 'node_type' => $type,
            'order_index' => $order, 'is_offered' => 1, 'is_active' => 1,
        ]);
    }

    private function level(string $name, int $order): void
    {
        AcademicLevel::create([
            'school_id' => $this->school->id, 'name' => $name, 'sequence_order' => $order, 'type' => 'basic',
        ]);
    }

    public function test_bucket_bridges_academic_levels_to_stage_groups_including_kinder(): void
    {
        $levels = AcademicLevel::where('school_id', $this->school->id)->get();
        $bucket = EducationLevels::bucketBasicLevels($levels);

        $byName = $levels->keyBy('name');
        $this->assertSame($this->preschoolId, $bucket[$byName['Kinder']->id]);   // Kinder ↔ Kindergarten
        $this->assertSame($this->elementaryId, $bucket[$byName['Grade 1']->id]);
        $this->assertSame($this->elementaryId, $bucket[$byName['Grade 2']->id]);
    }

    public function test_all_tab_shows_every_grade_level(): void
    {
        $this->actingAs($this->principal)
            ->get(route('principal.settings.grading'))
            ->assertOk()
            ->assertSee('All Grade Levels')
            ->assertSee('Preschool')
            ->assertSee('Elementary')
            ->assertSee('Kinder')
            ->assertSee('Grade 1');
    }

    public function test_a_stage_tab_shows_only_its_levels(): void
    {
        $this->actingAs($this->principal)
            ->get(route('principal.settings.grading', ['level' => $this->elementaryId]))
            ->assertOk()
            ->assertSee('Grade 1')
            ->assertSee('Grade 2')
            ->assertDontSee('>Kinder<', false); // Kinder card hidden on the Elementary tab
    }
}
