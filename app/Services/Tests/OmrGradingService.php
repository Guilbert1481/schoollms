<?php

namespace App\Services\Tests;

use App\Models\OmrItemResult;
use App\Models\OmrResult;
use App\Models\OmrScanAttempt;
use App\Models\OmrSheet;
use App\Services\Tests\Exceptions\DuplicateScanException;
use App\Support\AnswerMatch;
use Illuminate\Support\Facades\DB;

/**
 * Grades a set of marked answers against a sheet's frozen answer key and records
 * the result. Every submission is appended as an OmrScanAttempt (audit — never
 * mutated); the single OmrResult per sheet points at the attempt that produced
 * it. A second scan on an already-graded sheet is refused unless re-scan is
 * explicitly authorized, so a score is never silently overwritten.
 */
class OmrGradingService
{
    /**
     * @param  array<int, array<string, mixed>>  $markedAnswers  bubble marks: [{n, marks:[]}]
     * @param  array<int, array<string, mixed>>  $writtenAnswers  write-in text: [{n, text}]
     */
    public function record(
        OmrSheet $sheet,
        array $markedAnswers,
        array $writtenAnswers,
        ?int $scannedBy,
        string $source = 'manual',
        array $meta = [],
        ?array $confidence = null,
        bool $allowRescan = false,
        bool $isOverride = false,
    ): OmrResult {
        $current = OmrResult::where('omr_sheet_id', $sheet->id)->first();
        if ($current && ! $allowRescan && ! $isOverride) {
            throw new DuplicateScanException($sheet->id);
        }

        $marks = $this->normalise($markedAnswers);
        $written = $this->normaliseWritten($writtenAnswers);
        $graded = $this->grade($sheet, $marks, $written);

        return DB::transaction(function () use ($sheet, $marks, $written, $scannedBy, $source, $meta, $confidence, $graded, $isOverride) {
            $attempt = OmrScanAttempt::create([
                'school_id' => $sheet->school_id,
                'omr_sheet_id' => $sheet->id,
                'scanned_by' => $scannedBy,
                'source' => $source,
                'marked_answers' => $this->marksForStorage($marks),
                'written_answers' => $this->writtenForStorage($written) ?: null,
                'confidence' => $confidence,
                'meta' => $meta ?: null,
                'outcome' => $graded['result'],
            ]);

            $result = OmrResult::updateOrCreate(
                ['omr_sheet_id' => $sheet->id],
                array_merge($graded['result'], [
                    'school_id' => $sheet->school_id,
                    'scan_attempt_id' => $attempt->id,
                    'is_override' => $isOverride,
                    'graded_at' => now(),
                ]),
            );

            // Per-item rows always reflect the CURRENT result; prior marks remain
            // recoverable from the scan attempts.
            OmrItemResult::where('omr_result_id', $result->id)->delete();
            foreach ($graded['items'] as $item) {
                OmrItemResult::create([
                    'omr_result_id' => $result->id,
                    'item_number' => $item['n'],
                    'question_id' => $item['question_id'],
                    'marked' => $item['marked'],
                    'correct_label' => $item['correct'],
                    'outcome' => $item['outcome'],
                ]);
            }

            return $result->load('items');
        });
    }

    /**
     * Grade bubble marks + write-in answers against the sheet's frozen keys.
     *
     * @param  array<int, array<int,string>>  $marks  item number → marked letters
     * @param  array<int, string>  $written  item number → written text
     * @return array{result:array<string,mixed>, items:array<int,array<string,mixed>>}
     */
    private function grade(OmrSheet $sheet, array $marks, array $written): array
    {
        $items = [];
        $correct = $incorrect = $blank = $multiple = 0;

        foreach ($sheet->answer_key as $k) {
            $n = (int) $k['n'];
            $m = $marks[$n] ?? [];
            $correctLabel = $k['correct'] ?? null;

            if (count($m) === 0) {
                $outcome = 'blank';
                $blank++;
                $marked = null;
            } elseif (count($m) > 1) {
                $outcome = 'multiple';
                $multiple++;
                $marked = implode(',', $m);
            } elseif ($correctLabel !== null && $m[0] === $correctLabel) {
                $outcome = 'correct';
                $correct++;
                $marked = $m[0];
            } else {
                $outcome = 'incorrect';
                $incorrect++;
                $marked = $m[0];
            }

            $items[] = [
                'n' => $n,
                'question_id' => $k['question_id'] ?? null,
                'marked' => $marked,
                'correct' => $correctLabel,
                'outcome' => $outcome,
            ];
        }

        // Identification / matching: normalise + fuzzy-match the written answer.
        foreach (($sheet->written_key ?? []) as $k) {
            $n = (int) $k['n'];
            $text = $written[$n] ?? '';
            $accept = $k['accept'] ?? [];

            if (trim($text) === '') {
                $outcome = 'blank';
                $blank++;
            } elseif (AnswerMatch::matches($text, $accept)) {
                $outcome = 'correct';
                $correct++;
            } else {
                $outcome = 'incorrect';
                $incorrect++;
            }

            $items[] = [
                'n' => $n,
                'question_id' => $k['question_id'] ?? null,
                'marked' => $text !== '' ? $text : null,
                'correct' => $k['correct'] ?? null,
                'outcome' => $outcome,
            ];
        }

        $max = (int) $sheet->max_score;

        return [
            'result' => [
                'raw_score' => $correct,
                'max_score' => $max,
                'percentage' => $max > 0 ? round($correct / $max * 100, 2) : 0,
                'correct_count' => $correct,
                'incorrect_count' => $incorrect,
                'blank_count' => $blank,
                'multiple_count' => $multiple,
            ],
            'items' => $items,
        ];
    }

    /**
     * Normalise submitted answers to item number → distinct uppercase letters.
     *
     * @param  array<int, array<string, mixed>>  $input
     * @return array<int, array<int,string>>
     */
    private function normalise(array $input): array
    {
        $out = [];
        foreach ($input as $row) {
            if (! isset($row['n'])) {
                continue;
            }
            $marks = array_values(array_unique(array_filter(
                array_map(fn ($x) => strtoupper(trim((string) $x)), (array) ($row['marks'] ?? [])),
                fn ($x) => $x !== '',
            )));
            $out[(int) $row['n']] = $marks;
        }

        return $out;
    }

    /**
     * @param  array<int, array<int,string>>  $marks
     * @return array<int, array{n:int, marks:array<int,string>}>
     */
    private function marksForStorage(array $marks): array
    {
        $out = [];
        foreach ($marks as $n => $m) {
            $out[] = ['n' => $n, 'marks' => array_values($m)];
        }

        return $out;
    }

    /**
     * Normalise write-in submissions to item number → trimmed text.
     *
     * @param  array<int, array<string, mixed>>  $input  [{n, text}]
     * @return array<int, string>
     */
    private function normaliseWritten(array $input): array
    {
        $out = [];
        foreach ($input as $row) {
            if (! isset($row['n'])) {
                continue;
            }
            $out[(int) $row['n']] = trim((string) ($row['text'] ?? ''));
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $written
     * @return array<int, array{n:int, text:string}>
     */
    private function writtenForStorage(array $written): array
    {
        $out = [];
        foreach ($written as $n => $text) {
            $out[] = ['n' => $n, 'text' => $text];
        }

        return $out;
    }
}
