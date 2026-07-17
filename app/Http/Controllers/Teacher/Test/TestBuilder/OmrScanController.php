<?php

namespace App\Http\Controllers\Teacher\Test\TestBuilder;

use App\Http\Controllers\Controller;
use App\Models\OmrSheet;
use App\Services\Tests\Exceptions\DuplicateScanException;
use App\Services\Tests\OmrGradingService;
use App\Services\Tests\OmrSheetTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Records a scan/manual submission for one OMR sheet and returns the graded
 * result. The scanned image never reaches here — only the sheet token, marked
 * answers, and (Phase 2b) confidence/metadata. Duplicate scans are refused
 * unless re-scan is authorized.
 */
class OmrScanController extends Controller
{
    public function __construct(
        private OmrSheetTokenService $tokens,
        private OmrGradingService $grading,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sheet_token' => ['required', 'string'],
            'marked_answers' => ['present', 'array'],
            'marked_answers.*.n' => ['required', 'integer', 'min:1'],
            'marked_answers.*.marks' => ['array'],
            'marked_answers.*.marks.*' => ['string', 'max:2'],
            'written_answers' => ['nullable', 'array'],
            'written_answers.*.n' => ['required', 'integer', 'min:1'],
            'written_answers.*.text' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'in:manual,camera'],
            'allow_rescan' => ['nullable', 'boolean'],
            'is_override' => ['nullable', 'boolean'],
            'confidence' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);

        $decoded = $this->tokens->verifySheet($data['sheet_token']);
        if ($decoded === null) {
            return response()->json(['error' => 'Invalid or tampered sheet code.'], 422);
        }

        $sheet = OmrSheet::where('token', $decoded['token'])->first();
        if ($sheet === null) {
            return response()->json(['error' => 'Sheet not found.'], 404);
        }

        // Tenant guard: never grade another school's sheet.
        abort_unless((int) $sheet->school_id === (int) auth()->user()->school_id, 404);

        if ($decoded['version'] !== $sheet->layout_version) {
            return response()->json(['error' => 'Sheet layout version mismatch.'], 409);
        }

        try {
            $result = $this->grading->record(
                sheet: $sheet,
                markedAnswers: $data['marked_answers'],
                writtenAnswers: $data['written_answers'] ?? [],
                scannedBy: (int) auth()->id(),
                source: $data['source'] ?? 'manual',
                meta: $data['meta'] ?? [],
                confidence: $data['confidence'] ?? null,
                allowRescan: (bool) ($data['allow_rescan'] ?? false),
                isOverride: (bool) ($data['is_override'] ?? false),
            );
        } catch (DuplicateScanException $e) {
            return response()->json([
                'error' => 'already_scanned',
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'ok' => true,
            'sheet_id' => $sheet->id,
            'result' => $result->only([
                'raw_score', 'max_score', 'percentage',
                'correct_count', 'incorrect_count', 'blank_count', 'multiple_count', 'is_override',
            ]),
            'items' => $result->items->map->only(['item_number', 'marked', 'correct_label', 'outcome']),
        ]);
    }
}
