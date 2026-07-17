<?php

namespace App\Services\Tests;

use App\Models\OmrSheet;
use App\Models\Test;
use App\Support\OmrLayout;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Freezes the immutable answer-key snapshot for a student's sheet: the objective
 * (MCQ/TF) questions in printed order, each choice labelled A–E with the correct
 * label, plus the fixed bubble coordinates (OmrLayout) and a unique lookup token
 * for the QR. Created once per (test, student) and never rewritten — grading runs
 * against this, so editing the test later can't change an already-printed sheet.
 */
class OmrSheetSnapshotService
{
    private const OPTION_LETTERS = 5; // A–E

    public function forStudent(Test $test, int $studentId, ?int $sectionId = null): OmrSheet
    {
        $existing = OmrSheet::where('test_id', $test->id)->where('student_id', $studentId)->first();
        if ($existing) {
            return $existing;
        }

        $items = $this->orderedItems($test);
        $key = $this->answerKey($items);
        $written = $this->writtenKey($items);

        try {
            return OmrSheet::create([
                'school_id' => $test->school_id,
                'test_id' => $test->id,
                'student_id' => $studentId,
                'section_id' => $sectionId,
                'layout_version' => OmrLayout::VERSION,
                'token' => $this->uniqueToken(),
                'answer_key' => $key,
                'written_key' => $written,
                'item_count' => count($key),
                'written_count' => count($written),
                'max_score' => count($key) + count($written),
                'generated_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Lost a race on the (test_id, student_id) unique key — reuse the winner.
            return OmrSheet::where('test_id', $test->id)->where('student_id', $studentId)->firstOrFail();
        }
    }

    /**
     * The test's OMR items ordered into the canonical section sequence and
     * numbered 1..N (OmrLayout::sequence), each carrying its question model. The
     * live sheet (OmrSheetService) sequences the same way, so the frozen numbers
     * match what gets printed and scanned.
     *
     * @return array<int, array<string, mixed>>
     */
    private function orderedItems(Test $test): array
    {
        $raw = $test->testQuestions()
            ->with('question.choices')
            ->orderBy('order')
            ->get()
            ->filter(fn ($tq) => $tq->question && OmrLayout::isSupported($tq->question->question_type))
            ->map(fn ($tq) => ['type' => $tq->question->question_type, 'order' => (int) $tq->order, 'q' => $tq->question])
            ->values()
            ->all();

        return OmrLayout::sequence($raw, $test->print_seed);
    }

    /**
     * Immutable bubble map: the bubble items (True/False, Multiple Choice) with
     * their frozen item number, question id, labelled choices, and correct label.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function answerKey(array $items): array
    {
        $key = [];
        foreach ($items as $item) {
            if ($item['kind'] !== 'bubble') {
                continue;
            }
            $q = $item['q'];
            $options = [];
            $correct = null;

            foreach ($q->choices->sortBy('id')->values() as $ci => $choice) {
                if ($ci >= self::OPTION_LETTERS) {
                    break;
                }
                $label = chr(65 + $ci);
                $options[] = ['label' => $label, 'choice_id' => $choice->id];
                if ($choice->is_correct) {
                    $correct = $label;
                }
            }

            $key[] = [
                'n' => $item['n'],
                'question_id' => $q->id,
                'type' => $q->question_type,
                'correct' => $correct,
                'options' => $options,
            ];
        }

        return $key;
    }

    /**
     * Frozen write-in key: the write items (Matching, Identification) with their
     * correct answer text. Both are graded the same way (the student writes the
     * answer term), so matching needs no Column-B letter mapping.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function writtenKey(array $items): array
    {
        $key = [];
        foreach ($items as $item) {
            if ($item['kind'] !== 'write') {
                continue;
            }
            $q = $item['q'];
            $correct = optional($q->choices->first())->choice_text;

            $key[] = [
                'n' => $item['n'],
                'question_id' => $q->id,
                'type' => $item['canonical'],
                'correct' => $correct,
                'accept' => array_values(array_filter([$correct], fn ($v) => $v !== null && $v !== '')),
            ];
        }

        return $key;
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(40);
        } while (OmrSheet::where('token', $token)->exists());

        return $token;
    }
}
