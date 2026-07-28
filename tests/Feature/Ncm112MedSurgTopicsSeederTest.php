<?php

namespace Tests\Feature;

use App\Models\School;
use Database\Seeders\Ncm112MedSurgTopicsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * The Med-Surg topics attach as a flat, ordered list under NCM 112 and never
 * duplicate on re-run. The seeder also refuses to run if NCM 112 is missing.
 */
class Ncm112MedSurgTopicsSeederTest extends TestCase
{
    use RefreshDatabase;

    private const TOPIC_COUNT = 18;

    protected function setUp(): void
    {
        parent::setUp();

        if (! School::find(1)) {
            School::factory()->create(['id' => 1]);
        }
    }

    private function makeNcm112(): int
    {
        return DB::table('subjects')->insertGetId([
            'school_id' => 1,
            'code' => 'NCM-112',
            'name' => 'Care of Clients w/ Problems in Oxygenation...',
            'is_basic_ed' => 0,
            'scope' => 'academic',
            'category' => 'prof_ed',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_it_seeds_flat_ordered_topics_under_ncm112(): void
    {
        $subjectId = $this->makeNcm112();

        (new Ncm112MedSurgTopicsSeeder)->setContainer($this->app)->run();

        $topics = DB::table('topics')->where('subject_id', $subjectId)->orderBy('sort_order')->get();

        $this->assertCount(self::TOPIC_COUNT, $topics);
        $this->assertSame('Anatomy of the Heart', $topics->first()->name);
        $this->assertSame('Perioperative Nursing Concepts', $topics->last()->name);

        // sort_order is a contiguous 1..N sequence.
        $this->assertSame(range(1, self::TOPIC_COUNT), $topics->pluck('sort_order')->map(fn ($v) => (int) $v)->all());
    }

    public function test_re_running_does_not_duplicate_topics(): void
    {
        $subjectId = $this->makeNcm112();

        $seeder = (new Ncm112MedSurgTopicsSeeder)->setContainer($this->app);
        $seeder->run();
        $seeder->run();

        $this->assertSame(self::TOPIC_COUNT, DB::table('topics')->where('subject_id', $subjectId)->count());
        $this->assertSame(1, DB::table('topics')->where('subject_id', $subjectId)->where('name', 'Hypertension')->count());
    }

    public function test_it_fails_loudly_when_ncm112_is_absent(): void
    {
        $this->expectException(RuntimeException::class);

        (new Ncm112MedSurgTopicsSeeder)->setContainer($this->app)->run();
    }
}
