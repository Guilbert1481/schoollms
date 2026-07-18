<?php

namespace Tests\Feature;

use App\Models\OmrScanAttempt;
use App\Models\OmrSheet;
use App\Models\Question;
use App\Models\School;
use App\Models\Test;
use App\Models\User;
use App\Services\Tests\OmrSheetSnapshotService;
use App\Services\Tests\OmrSheetTokenService;
use App\Support\TestArrangement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 2a grading backbone: mark answers → score/counts/per-item, refuse a
 * silent duplicate scan, support authorized re-scan/override, and keep every
 * attempt as audit (the original result is never destroyed).
 */
class OmrGradingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private int $levelId;

    private int $topicId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $this->levelId = DB::table('academic_levels')->insertGetId([
            'school_id' => $this->school->id, 'name' => 'Grade 8', 'sequence_order' => 8, 'type' => 'basic',
        ]);
        $subjectId = DB::table('subjects')->insertGetId([
            'school_id' => $this->school->id, 'name' => 'Math', 'code' => 'M8', 'scope' => 'academic',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->topicId = DB::table('topics')->insertGetId([
            'school_id' => $this->school->id, 'subject_id' => $subjectId, 'name' => 'T',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Adds an objective question whose choices' correct index gives the label. */
    private function question(Test $test, int $order, string $type, array $choices, int $correctIndex): void
    {
        $qid = DB::table('questions')->insertGetId([
            'school_id' => $this->school->id, 'topic_id' => $this->topicId, 'teacher_id' => $this->teacher->id,
            'academic_level_id' => $this->levelId, 'question_type' => $type, 'question_text' => 'Q',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($choices as $i => $text) {
            DB::table('choices')->insert([
                'question_id' => $qid, 'choice_text' => $text, 'is_correct' => $i === $correctIndex,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('test_questions')->insert([
            'test_id' => $test->id, 'question_id' => $qid, 'order' => $order,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** A test + snapshot sheet with correct answers A, B, A, C over 4 items. */
    private function makeSheet(?int $schoolId = null): OmrSheet
    {
        $test = Test::create([
            'school_id' => $schoolId ?? $this->school->id, 'teacher_id' => $this->teacher->id,
            'title' => 'Exam', 'status' => 'draft',
        ]);
        $this->question($test, 1, 'mcq', ['w', 'w', 'w', 'w'], 0);          // correct A
        $this->question($test, 2, 'mcq', ['w', 'w', 'w', 'w'], 1);          // correct B
        $this->question($test, 3, 'true_false', ['True', 'False'], 0);      // correct A
        $this->question($test, 4, 'mcq', ['w', 'w', 'w', 'w'], 2);          // correct C

        return app(OmrSheetSnapshotService::class)->forStudent($test, 555, null);
    }

    private function qr(OmrSheet $sheet): string
    {
        return app(OmrSheetTokenService::class)->sheetToken($sheet->token, $sheet->layout_version);
    }

    /** @param array<int,array<int,string>> $marks item → letters */
    private function payload(OmrSheet $sheet, array $marks, array $extra = []): array
    {
        $answers = [];
        foreach ($marks as $n => $letters) {
            $answers[] = ['n' => $n, 'marks' => $letters];
        }

        return array_merge(['sheet_token' => $this->qr($sheet), 'marked_answers' => $answers], $extra);
    }

    public function test_snapshot_freezes_the_answer_key(): void
    {
        $sheet = $this->makeSheet();

        $this->assertSame(4, $sheet->item_count);
        $this->assertSame(4, $sheet->max_score);
        $this->assertSame(['A', 'B', 'A', 'C'], array_column($sheet->answer_key, 'correct'));
    }

    public function test_grades_marks_into_score_counts_and_per_item(): void
    {
        $sheet = $this->makeSheet();

        // 1 correct, 1 wrong, 1 blank, 1 multiple.
        $res = $this->actingAs($this->teacher)
            ->postJson(route('teacher.tests.omr.scan'), $this->payload($sheet, [
                1 => ['A'],        // correct
                2 => ['A'],        // wrong (correct B)
                3 => [],           // blank
                4 => ['A', 'B'],   // multiple
            ]))
            ->assertOk()->json();

        $this->assertSame(1, $res['result']['raw_score']);
        $this->assertSame(4, $res['result']['max_score']);
        $this->assertSame('25.00', (string) $res['result']['percentage']);
        $this->assertSame(1, $res['result']['correct_count']);
        $this->assertSame(1, $res['result']['incorrect_count']);
        $this->assertSame(1, $res['result']['blank_count']);
        $this->assertSame(1, $res['result']['multiple_count']);

        $outcomes = array_column($res['items'], 'outcome');
        $this->assertSame(['correct', 'incorrect', 'blank', 'multiple'], $outcomes);
    }

    public function test_grades_identification_and_matching_as_written_answers(): void
    {
        $test = Test::create([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'title' => 'Exam', 'status' => 'draft',
        ]);
        $this->question($test, 1, 'mcq', ['w', 'w', 'w', 'w'], 0);         // item 1 (bubble) correct A
        $this->question($test, 2, 'identification', ['Photosynthesis'], 0); // item 2 (written)
        $this->question($test, 3, 'matching', ['Tokyo'], 0);               // item 3 (written)

        $sheet = app(OmrSheetSnapshotService::class)->forStudent($test, 777, null);

        $this->assertSame(1, $sheet->item_count);
        $this->assertSame(2, $sheet->written_count);
        $this->assertSame(3, $sheet->max_score);

        $res = $this->actingAs($this->teacher)
            ->postJson(route('teacher.tests.omr.scan'), [
                'sheet_token' => app(OmrSheetTokenService::class)->sheetToken($sheet->token, $sheet->layout_version),
                'marked_answers' => [['n' => 1, 'marks' => ['A']]],
                'written_answers' => [
                    ['n' => 2, 'text' => '  photosynthesis! '], // fuzzy → correct
                    ['n' => 3, 'text' => 'Paris'],              // wrong (correct is Tokyo)
                ],
            ])
            ->assertOk()->json();

        // 1 bubble correct + 1 written correct + 1 written wrong = 2/3.
        $this->assertSame(2, $res['result']['raw_score']);
        $this->assertSame(3, $res['result']['max_score']);
        $this->assertSame(2, $res['result']['correct_count']);
        $this->assertSame(1, $res['result']['incorrect_count']);
        $this->assertSame(['correct', 'correct', 'incorrect'], array_column($res['items'], 'outcome'));
    }

    public function test_duplicate_scan_is_refused_then_allowed_with_authorization(): void
    {
        $sheet = $this->makeSheet();

        $this->actingAs($this->teacher)
            ->postJson(route('teacher.tests.omr.scan'), $this->payload($sheet, [1 => ['A']]))
            ->assertOk();

        // Second scan without authorization is blocked.
        $this->actingAs($this->teacher)
            ->postJson(route('teacher.tests.omr.scan'), $this->payload($sheet, [1 => ['B']]))
            ->assertStatus(409)
            ->assertJson(['error' => 'already_scanned']);

        // Authorized re-scan succeeds and both attempts are kept as audit.
        $this->actingAs($this->teacher)
            ->postJson(route('teacher.tests.omr.scan'), $this->payload($sheet, [1 => ['B']], ['allow_rescan' => true]))
            ->assertOk();

        $this->assertSame(2, OmrScanAttempt::where('omr_sheet_id', $sheet->id)->count());
    }

    public function test_override_updates_result_and_preserves_original_attempt(): void
    {
        $sheet = $this->makeSheet();

        $this->actingAs($this->teacher)
            ->postJson(route('teacher.tests.omr.scan'), $this->payload($sheet, [1 => ['A']]))
            ->assertOk();

        $res = $this->actingAs($this->teacher)
            ->postJson(route('teacher.tests.omr.scan'), $this->payload($sheet, [
                1 => ['A'], 2 => ['B'], 3 => ['A'], 4 => ['C'],
            ], ['is_override' => true]))
            ->assertOk()->json();

        $this->assertTrue($res['result']['is_override']);
        $this->assertSame(4, $res['result']['raw_score']);
        $this->assertSame(2, OmrScanAttempt::where('omr_sheet_id', $sheet->id)->count());
    }

    public function test_a_foreign_school_sheet_is_blocked(): void
    {
        $other = School::factory()->create();
        $sheet = $this->makeSheet($other->id);

        $this->actingAs($this->teacher)
            ->postJson(route('teacher.tests.omr.scan'), $this->payload($sheet, [1 => ['A']]))
            ->assertNotFound();
    }

    public function test_a_tampered_token_is_rejected(): void
    {
        $sheet = $this->makeSheet();
        $payload = $this->payload($sheet, [1 => ['A']]);
        $payload['sheet_token'] .= 'x';

        $this->actingAs($this->teacher)
            ->postJson(route('teacher.tests.omr.scan'), $payload)
            ->assertStatus(422);
    }

    /**
     * Grade-safety of choice-shuffle: with a print seed set, the snapshot labels
     * each MCQ's choices in TestArrangement::choiceOrder — the SAME order the
     * questionnaire and answer-key controllers use — so the frozen correct letter
     * (and the whole letter→choice_id map) matches what the student's sheet shows.
     * A one-sided change (e.g. the snapshot dropping choiceOrder) breaks this.
     */
    public function test_seeded_snapshot_labels_mcq_choices_in_arrangement_order(): void
    {
        $seed = 12345;
        $test = Test::create([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'title' => 'Exam', 'status' => 'draft', 'print_seed' => $seed,
        ]);
        $this->question($test, 1, 'mcq', ['alpha', 'bravo', 'charlie', 'delta'], 2); // correct "charlie"
        $this->question($test, 2, 'mcq', ['one', 'two', 'three', 'four'], 0);        // correct "one"
        $this->question($test, 3, 'true_false', ['True', 'False'], 0);               // correct A (unshuffled)

        $sheet = app(OmrSheetSnapshotService::class)->forStudent($test, 901, null);

        foreach ($sheet->answer_key as $entry) {
            $question = Question::with('choices')->findOrFail($entry['question_id']);

            $ordered = $question->question_type === 'multiple_choice'
                ? TestArrangement::choiceOrder($seed, $question->id, $question->choices)
                : $question->choices->sortBy('id')->values();

            // The frozen options are in the arrangement order (letter -> choice_id).
            $this->assertSame(
                $ordered->pluck('id')->all(),
                array_column($entry['options'], 'choice_id'),
                "Options for question {$question->id} must follow the arrangement order.",
            );

            // The frozen correct letter is the position of the correct choice in that order.
            $correctIndex = $ordered->search(fn ($c) => (bool) $c->is_correct);
            $this->assertSame(chr(65 + $correctIndex), $entry['correct']);
        }

        // True/False is never shuffled: correct choice stays label A.
        $tf = collect($sheet->answer_key)->firstWhere('type', 'true_false');
        $this->assertSame('A', $tf['correct']);
    }

    /**
     * A null seed leaves MCQ choices in id order — identical to the pre-shuffle
     * behaviour — so tests printed before this feature (and their already-frozen
     * snapshots) are unaffected. (test_snapshot_freezes_the_answer_key covers the
     * A,B,A,C key end-to-end; this pins the ordering contract directly.)
     */
    public function test_null_seed_keeps_choices_in_id_order(): void
    {
        $test = Test::create([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'title' => 'Exam', 'status' => 'draft', // print_seed defaults to null
        ]);
        $this->question($test, 1, 'mcq', ['alpha', 'bravo', 'charlie', 'delta'], 2);

        $question = Question::with('choices')->firstOrFail();
        $ordered = TestArrangement::choiceOrder(null, $question->id, $question->choices);

        $this->assertSame(
            $question->choices->sortBy('id')->pluck('id')->all(),
            $ordered->pluck('id')->all(),
        );
    }

    /**
     * The seed genuinely permutes choices (not a no-op) and is a faithful
     * permutation — every choice survives exactly once, so no answer is dropped or
     * duplicated — and it is deterministic for a given seed.
     */
    public function test_seeded_choice_order_is_a_deterministic_permutation(): void
    {
        $test = Test::create([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'title' => 'Exam', 'status' => 'draft',
        ]);
        $this->question($test, 1, 'mcq', ['a', 'b', 'c', 'd', 'e'], 0);
        $question = Question::with('choices')->firstOrFail();

        $naturalIds = $question->choices->sortBy('id')->pluck('id')->all();

        // Same members every time (permutation) and stable for a fixed seed.
        $first = TestArrangement::choiceOrder(2024, $question->id, $question->choices)->pluck('id')->all();
        $again = TestArrangement::choiceOrder(2024, $question->id, $question->choices)->pluck('id')->all();
        $this->assertSame($first, $again, 'Same seed must be deterministic.');
        $this->assertEqualsCanonicalizing($naturalIds, $first, 'Every choice must survive exactly once.');

        // At least one seed in a small range reorders away from natural id order.
        $reordered = collect(range(1, 40))->contains(
            fn ($s) => TestArrangement::choiceOrder($s, $question->id, $question->choices)->pluck('id')->all() !== $naturalIds
        );
        $this->assertTrue($reordered, 'A seed must be able to change the choice order.');
    }
}
