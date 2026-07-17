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
    private const BUBBLE_TYPES = ['mcq', 'multiple_choice', 'tf', 'true_false'];

    private const WRITE_TYPES = ['identification', 'id', 'matching', 'match'];

    private const OPTION_LETTERS = 5; // A–E

    public function forStudent(Test $test, int $studentId, ?int $sectionId = null): OmrSheet
    {
        $existing = OmrSheet::where('test_id', $test->id)->where('student_id', $studentId)->first();
        if ($existing) {
            return $existing;
        }

        $key = $this->answerKey($test);
        $written = $this->writtenKey($test, count($key));

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
     * Immutable bubble map: item order, question id, labelled choices, correct
     * label, and A–E bubble coordinates.
     *
     * @return array<int, array<string, mixed>>
     */
    private function answerKey(Test $test): array
    {
        $questions = $test->testQuestions()
            ->with('question.choices')
            ->orderBy('order')
            ->get()
            ->map(fn ($tq) => $tq->question)
            ->filter(fn ($q) => $q && in_array($q->question_type, self::BUBBLE_TYPES, true))
            ->values();

        $coords = OmrLayout::map($questions->count(), self::OPTION_LETTERS);

        $key = [];
        foreach ($questions as $i => $q) {
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
                'n' => $i + 1,
                'question_id' => $q->id,
                'type' => $q->question_type,
                'correct' => $correct,
                'options' => $options,
                'bubbles' => $coords[$i]['options'] ?? [],
            ];
        }

        return $key;
    }

    /**
     * Frozen write-in key: identification + matching questions in printed order,
     * each with its correct answer text. Both are graded the same way (the student
     * writes the answer term), so matching needs no Column-B letter mapping.
     * Numbered continuously after the bubble items.
     *
     * @return array<int, array<string, mixed>>
     */
    private function writtenKey(Test $test, int $offset): array
    {
        $questions = $test->testQuestions()
            ->with('question.choices')
            ->orderBy('order')
            ->get()
            ->map(fn ($tq) => $tq->question)
            ->filter(fn ($q) => $q && in_array($q->question_type, self::WRITE_TYPES, true))
            ->values();

        $key = [];
        foreach ($questions as $i => $q) {
            $correct = optional($q->choices->first())->choice_text;

            $key[] = [
                'n' => $offset + $i + 1,
                'question_id' => $q->id,
                'type' => in_array($q->question_type, ['matching', 'match'], true) ? 'matching' : 'identification',
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
