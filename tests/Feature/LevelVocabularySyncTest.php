<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\School;
use App\Services\Tests\LevelTreeResolver;
use App\Services\Tests\LevelVocabularySync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The education tree is the source of truth for levels: every OFFERED terminal
 * level node gets an academic_levels row so a teacher can tag/build for it. This
 * pins the additive, idempotent provisioning and that navigation-only nodes
 * (tracks/strands) and non-offered nodes are skipped.
 */
class LevelVocabularySyncTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolId = (int) School::factory()->create()->id;
        DB::table('academic_levels')->where('school_id', $this->schoolId)->delete();

        // Pre-existing vocabulary the sync must reuse, not duplicate.
        DB::table('academic_levels')->insert([
            ['school_id' => $this->schoolId, 'name' => 'Kinder', 'sequence_order' => 0, 'type' => 'basic'],
            ['school_id' => $this->schoolId, 'name' => 'Grade 1', 'sequence_order' => 1, 'type' => 'basic'],
        ]);
    }

    private function node(string $name, string $type, ?int $parentId = null, int $order = 0, bool $offered = true): int
    {
        return DB::table('education_nodes')->insertGetId([
            'name' => $name, 'parent_id' => $parentId, 'node_type' => $type,
            'order_index' => $order, 'is_active' => true, 'is_offered' => $offered,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_provisions_missing_terminal_levels_and_reuses_existing(): void
    {
        $basic = $this->node('Basic Education', 'level');
        $pre = $this->node('Preschool', 'stage', $basic);
        $this->node('Toddler', 'stage', $pre, 1);
        $this->node('Nursery', 'stage', $pre, 2);
        $this->node('Kindergarten', 'stage', $pre, 3); // → reuses existing "Kinder"
        $elem = $this->node('Elementary', 'stage', $basic);
        $this->node('Grade 1', 'stage', $elem, 1); // already exists → no dup

        $created = (new LevelVocabularySync)->syncForSchool($this->schoolId);

        $this->assertSame(2, $created); // Toddler + Nursery only
        $this->assertDatabaseHas('academic_levels', ['school_id' => $this->schoolId, 'name' => 'Toddler', 'type' => 'basic']);
        $this->assertDatabaseHas('academic_levels', ['school_id' => $this->schoolId, 'name' => 'Nursery', 'type' => 'basic']);
        // No duplicate Kinder / Grade 1.
        $this->assertSame(1, AcademicLevel::where('school_id', $this->schoolId)->where('name', 'Kinder')->count());
        $this->assertSame(1, AcademicLevel::where('school_id', $this->schoolId)->where('name', 'Grade 1')->count());

        // Idempotent.
        $this->assertSame(0, (new LevelVocabularySync)->syncForSchool($this->schoolId));
    }

    public function test_skips_tracks_strands_and_non_offered_nodes(): void
    {
        $basic = $this->node('Basic Education', 'level');
        $shs = $this->node('Senior High School', 'stage', $basic);
        $track = $this->node('Academic Track', 'track', $shs);
        $this->node('STEM', 'strand', $track);              // strand → not a level
        $this->node('Nursery', 'stage', $basic, 9, offered: false); // not offered → skipped

        (new LevelVocabularySync)->syncForSchool($this->schoolId);

        $this->assertDatabaseMissing('academic_levels', ['school_id' => $this->schoolId, 'name' => 'STEM']);
        $this->assertDatabaseMissing('academic_levels', ['school_id' => $this->schoolId, 'name' => 'Academic Track']);
        $this->assertDatabaseMissing('academic_levels', ['school_id' => $this->schoolId, 'name' => 'Nursery']);
    }

    public function test_synced_levels_become_selectable_in_the_resolver(): void
    {
        $basic = $this->node('Basic Education', 'level');
        $pre = $this->node('Preschool', 'stage', $basic);
        $this->node('Toddler', 'stage', $pre, 1);
        $this->node('Nursery', 'stage', $pre, 2);
        $this->node('Kindergarten', 'stage', $pre, 3);

        (new LevelVocabularySync)->syncForSchool($this->schoolId);

        $map = (new LevelTreeResolver)->levelsByNode($this->schoolId);
        $names = array_column($map[$pre] ?? [], 'name');

        $this->assertSame(['Toddler', 'Nursery', 'Kinder'], $names);
    }
}
