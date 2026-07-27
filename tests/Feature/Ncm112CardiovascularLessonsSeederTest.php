<?php

namespace Tests\Feature;

use App\Models\School;
use Database\Seeders\Ncm112CardiovascularLessonsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Lessons and their competencies attach under the four cardiovascular topics of
 * NCM 112 without duplicating on re-run, and a missing topic is skipped rather
 * than aborting the whole seed.
 */
class Ncm112CardiovascularLessonsSeederTest extends TestCase
{
    use RefreshDatabase;

    /** 8 + 12 + 7 + 9 lessons across the four topics. */
    private const LESSON_TOTAL = 36;

    private const TOPICS = ['Cardiac Fundamentals', 'Cardiovascular Diagnostic Procedures', 'Cardiomyopathy', 'Congestive Heart Failure (CHF)'];

    private int $subjectId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! School::find(1)) {
            School::factory()->create(['id' => 1]);
        }

        $this->subjectId = DB::table('subjects')->insertGetId([
            'school_id' => 1, 'code' => 'NCM-112', 'name' => 'Care of Clients w/ Problems in Oxygenation...',
            'is_basic_ed' => 0, 'scope' => 'academic', 'category' => 'prof_ed', 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeTopics(array $names): void
    {
        foreach ($names as $i => $name) {
            DB::table('topics')->insert([
                'school_id' => 1, 'subject_id' => $this->subjectId, 'name' => $name,
                'sort_order' => $i + 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function test_it_seeds_lessons_and_competencies_under_each_topic(): void
    {
        $this->makeTopics(self::TOPICS);

        (new Ncm112CardiovascularLessonsSeeder)->setContainer($this->app)->run();

        $this->assertSame(self::LESSON_TOTAL, DB::table('lessons')->where('subject_id', $this->subjectId)->count());
        $this->assertSame(self::LESSON_TOTAL, DB::table('competencies')->where('subject_id', $this->subjectId)->count());

        // Every lesson has exactly one competency, bound by lesson_id.
        $orphans = DB::table('lessons as l')
            ->where('l.subject_id', $this->subjectId)
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('competencies as c')->whereColumn('c.lesson_id', 'l.id');
            })->count();
        $this->assertSame(0, $orphans, 'Every seeded lesson must carry a competency.');

        // CHF topic has its 9 lessons in order.
        $chfTopicId = DB::table('topics')->where('name', 'Congestive Heart Failure (CHF)')->value('id');
        $chf = DB::table('lessons')->where('topic_id', $chfTopicId)->orderBy('sort_order')->pluck('name');
        $this->assertCount(9, $chf);
        $this->assertSame('Overview & Pathophysiology', $chf->first());
        $this->assertSame('Health Education & Discharge Planning', $chf->last());
    }

    public function test_re_running_is_idempotent(): void
    {
        $this->makeTopics(self::TOPICS);

        $seeder = (new Ncm112CardiovascularLessonsSeeder)->setContainer($this->app);
        $seeder->run();
        $seeder->run();

        $this->assertSame(self::LESSON_TOTAL, DB::table('lessons')->where('subject_id', $this->subjectId)->count());
        $this->assertSame(self::LESSON_TOTAL, DB::table('competencies')->where('subject_id', $this->subjectId)->count());
    }

    public function test_missing_topics_are_skipped_not_fatal(): void
    {
        // Only two of the four topics exist.
        $this->makeTopics(['Cardiac Fundamentals', 'Cardiomyopathy']);

        (new Ncm112CardiovascularLessonsSeeder)->setContainer($this->app)->run();

        // 8 (Cardiac Fundamentals) + 7 (Cardiomyopathy) = 15 lessons; the other two topics are skipped.
        $this->assertSame(15, DB::table('lessons')->where('subject_id', $this->subjectId)->count());
    }

    public function test_it_throws_when_subject_absent(): void
    {
        DB::table('subjects')->where('id', $this->subjectId)->delete();
        $this->expectException(RuntimeException::class);

        (new Ncm112CardiovascularLessonsSeeder)->setContainer($this->app)->run();
    }
}
