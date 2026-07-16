<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\Tests\LevelTreeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Test Builder's "Assessment Levels" cascade navigates the education-structure
 * tree (education_nodes) but must resolve every branch back to academic_levels ids
 * (the vocabulary questions are tagged with — see ADR-0006). The name bridge is the
 * fragile part, so this pins the known mismatches: "(Core)" suffix, "Kindergarten"
 * vs "Kinder", strands with no grade climbing to their SHS ancestor, per-program
 * "Year N", branches with no matching level, and Training → Review.
 */
class LevelTreeResolverTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    /** name => academic_level id (this school's vocabulary) */
    private array $lvl = [];

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::factory()->create();
        $this->schoolId = (int) $school->id;

        // Deterministic vocabulary — clear anything auto-seeded, then seed the
        // standard rows this resolver bridges to.
        DB::table('academic_levels')->where('school_id', $this->schoolId)->delete();

        $rows = [['Kinder', 0, 'basic']];
        for ($g = 1; $g <= 12; $g++) {
            $rows[] = ['Grade '.$g, $g, 'basic'];
        }
        for ($y = 1; $y <= 5; $y++) {
            $rows[] = ['Year '.$y, $y, 'higher'];
        }
        $rows[] = ['Training', 1, 'training'];
        $rows[] = ['Review', 1, 'review'];

        foreach ($rows as [$name, $seq, $type]) {
            $this->lvl[$name] = DB::table('academic_levels')->insertGetId([
                'school_id' => $this->schoolId, 'name' => $name, 'sequence_order' => $seq, 'type' => $type,
            ]);
        }
    }

    private function node(string $name, string $type, ?int $parentId = null, int $order = 0, bool $offered = true): int
    {
        return DB::table('education_nodes')->insertGetId([
            'name' => $name,
            'parent_id' => $parentId,
            'node_type' => $type,
            'order_index' => $order,
            'is_active' => true,
            'is_offered' => $offered,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<int,string> resolved academic-level names for a node */
    private function levelNames(array $map, int $nodeId): array
    {
        return array_column($map[$nodeId] ?? [], 'name');
    }

    public function test_basic_ed_segments_map_to_their_grade_leaves(): void
    {
        $basic = $this->node('Basic Education', 'level');
        $elem = $this->node('Elementary', 'stage', $basic);
        $this->node('Grade 1', 'stage', $elem, 1);
        $this->node('Grade 2', 'stage', $elem, 2);

        $map = (new LevelTreeResolver)->levelsByNode($this->schoolId);

        $this->assertSame(['Grade 1', 'Grade 2'], $this->levelNames($map, $elem));
    }

    public function test_shs_strand_with_no_grade_climbs_to_grade_11_12_and_strips_core_suffix(): void
    {
        $basic = $this->node('Basic Education', 'level');
        $shs = $this->node('Senior High School', 'stage', $basic);
        $acad = $this->node('Academic Track', 'track', $shs);
        $stem = $this->node('STEM', 'strand', $acad);
        $this->node('Grade 11 (Core)', 'stage', $shs, 5);
        $this->node('Grade 12 (Core)', 'stage', $shs, 6);

        $map = (new LevelTreeResolver)->levelsByNode($this->schoolId);

        // STEM carries no grade → climbs to SHS, and "(Core)" is stripped.
        $this->assertSame(['Grade 11', 'Grade 12'], $this->levelNames($map, $stem));
        $this->assertSame(['Grade 11', 'Grade 12'], $this->levelNames($map, $shs));
    }

    public function test_kindergarten_node_maps_to_kinder(): void
    {
        $basic = $this->node('Basic Education', 'level');
        $pre = $this->node('Preschool', 'stage', $basic);
        $this->node('Toddler', 'stage', $pre, 1);
        $kinder = $this->node('Kindergarten', 'stage', $pre, 3);

        $map = (new LevelTreeResolver)->levelsByNode($this->schoolId);

        $this->assertSame(['Kinder'], $this->levelNames($map, $kinder));
        // Toddler has no mappable grade of its own → climbs to Preschool → Kinder.
        $this->assertSame(['Kinder'], $this->levelNames($map, $pre));
    }

    public function test_higher_ed_program_maps_to_its_year_levels(): void
    {
        $ug = $this->node('Undergraduate Programs', 'level');
        $prog = $this->node('BSED - Math', 'stage', $ug);
        $y1 = $this->node('Year 1', 'track', $prog, 1);
        $this->node('Year 2', 'track', $prog, 2);

        $map = (new LevelTreeResolver)->levelsByNode($this->schoolId);

        $this->assertSame(['Year 1', 'Year 2'], $this->levelNames($map, $prog));
        $this->assertSame(['Year 1'], $this->levelNames($map, $y1));
    }

    public function test_branch_with_no_matching_level_resolves_empty(): void
    {
        $grad = $this->node('Graduate Programs', 'level');
        $mast = $this->node("Master's Degree", 'program_type', $grad);

        $map = (new LevelTreeResolver)->levelsByNode($this->schoolId);

        $this->assertSame([], $this->levelNames($map, $mast));
        $this->assertSame([], $this->levelNames($map, $grad));
    }

    public function test_training_root_covers_training_and_nested_review(): void
    {
        $training = $this->node('Training', 'level');
        $review = $this->node('Review', 'stage', $training);

        $map = (new LevelTreeResolver)->levelsByNode($this->schoolId);

        $this->assertEqualsCanonicalizing(['Training', 'Review'], $this->levelNames($map, $training));
        $this->assertSame(['Review'], $this->levelNames($map, $review));
    }

    public function test_non_offered_roots_are_excluded_from_the_tree(): void
    {
        $offered = $this->node('Basic Education', 'level', null, 0, offered: true);
        $notOffered = $this->node('Undergraduate Programs', 'level', null, 1, offered: false);
        // An offered child under the non-offered root must NOT surface either.
        $this->node('BSED - Math', 'stage', $notOffered, 0, offered: true);

        $tree = (new LevelTreeResolver)->tree();
        $ids = array_column($tree, 'id');

        $this->assertContains($offered, $ids);
        $this->assertNotContains($notOffered, $ids);
    }

    public function test_leaf_children_that_map_to_nothing_do_not_make_a_node_drillable(): void
    {
        // Preschool → Toddler/Nursery (no level) + Kindergarten (→ Kinder). None
        // have children, so no redundant sub-dropdown — the picker just offers Kinder.
        $basic = $this->node('Basic Education', 'level');
        $pre = $this->node('Preschool', 'stage', $basic);
        $this->node('Toddler', 'stage', $pre, 1);
        $this->node('Nursery', 'stage', $pre, 2);
        $this->node('Kindergarten', 'stage', $pre, 3);

        $resolver = new LevelTreeResolver;
        $tree = $resolver->tree();
        $preNode = collect(collect($tree)->firstWhere('id', $basic)['children'])->firstWhere('id', $pre);

        $this->assertFalse($preNode['drillable']);
        $this->assertSame(['Kinder'], $this->levelNames($resolver->levelsByNode($this->schoolId), $pre));
    }

    public function test_tree_marks_grade_leaf_parents_as_not_drillable(): void
    {
        $basic = $this->node('Basic Education', 'level');
        $elem = $this->node('Elementary', 'stage', $basic);
        $this->node('Grade 1', 'stage', $elem, 1);
        $this->node('Grade 2', 'stage', $elem, 2);

        $tree = (new LevelTreeResolver)->tree();

        $basicNode = collect($tree)->firstWhere('id', $basic);
        $elemNode = collect($basicNode['children'])->firstWhere('id', $elem);

        // Basic Education still needs navigation; Elementary's children are the
        // grade leaves themselves, so no redundant sub-dropdown.
        $this->assertTrue($basicNode['drillable']);
        $this->assertFalse($elemNode['drillable']);
    }
}
