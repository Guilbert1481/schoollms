<?php

namespace App\Services\Tests;

use App\Models\AcademicYear;
use App\Models\OmrItemResult;
use App\Models\OmrResult;
use App\Models\SchoolProfile;
use App\Models\Section;
use App\Models\Test;
use App\Support\OmrLayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Assembles everything the printable OMR answer sheets need for a test + section:
 * the letterhead/school-year header, the enrolled-student roster (via
 * student_enrollments — the students table has no section_id), a signed QR token
 * per student, and the number of A–E bubble rows (objective items only). Phase 1
 * is generation; scanning/grading the marks is a later phase.
 */
class OmrSheetService
{
    /**
     * Objective question types that map to A–E bubbles. Both the short codes
     * actually stored in `questions` (mcq/tf) and the long forms used elsewhere
     * in the builder are listed so the count is correct either way.
     */
    private const BUBBLE_TYPES = ['mcq', 'multiple_choice', 'tf', 'true_false'];

    private const WRITE_TYPES = ['identification', 'id', 'matching', 'match'];

    public function __construct(
        private OmrSheetTokenService $tokens,
        private OmrSheetSnapshotService $snapshots,
    ) {}

    /** Sections in the test's school that have enrolled students (print picker). */
    public function sectionsForPicker(Test $test): Collection
    {
        return DB::table('student_enrollments as se')
            ->join('sections as sec', 'sec.id', '=', 'se.section_id')
            ->where('sec.school_id', $test->school_id)
            ->where('se.status', 'enrolled')
            ->groupBy('sec.id', 'sec.name', 'sec.year_level')
            ->orderBy('sec.year_level')
            ->orderBy('sec.name')
            ->get([
                'sec.id',
                'sec.name',
                'sec.year_level',
                DB::raw('count(distinct se.student_id) as student_count'),
            ]);
    }

    /**
     * Sheet data for one section.
     *
     * @return array<string, mixed>
     */
    public function build(Test $test, Section $section): array
    {
        $meta = DB::table('student_enrollments')
            ->where('section_id', $section->id)
            ->where('status', 'enrolled')
            ->first(['academic_year_id', 'education_level']);

        $students = $this->enrolledStudents((int) $section->id);

        $sheets = $students->map(function ($s) use ($test, $section) {
            // Generating the printable sheets is exactly when the immutable
            // snapshot is frozen; the QR carries that sheet's signed token.
            $sheet = $this->snapshots->forStudent($test, (int) $s->id, (int) $section->id);

            return [
                'name' => $this->fullName($s),
                'student_number' => $s->student_number,
                'lrn' => $s->lrn,
                'qr' => $this->tokens->sheetToken($sheet->token, $sheet->layout_version),
            ];
        })->all();

        $itemCount = $this->objectiveItemCount($test);
        $written = $this->writtenItems($test, $itemCount);
        $layout = OmrLayout::regions($itemCount, count($written), 5);

        foreach ($written as $i => &$w) {
            $w['box'] = $layout['writes'][$i]['box'] ?? null;
            $w['num'] = $layout['writes'][$i]['num'] ?? null;
        }
        unset($w);

        return [
            'schoolYear' => $this->schoolYear((int) $test->school_id, $meta?->academic_year_id),
            'profile' => SchoolProfile::where('school_id', $test->school_id)->first(),
            'gradeLabel' => $this->gradeLabel($section, $meta?->education_level),
            'itemCount' => $itemCount,
            'grid' => $layout['bubbles'],
            'fiducials' => OmrLayout::fiducials(),
            'layoutVersion' => OmrLayout::VERSION,
            'regionHeight' => $layout['region_height_in'],
            'written' => $written,
            'sheets' => $sheets,
        ];
    }

    /**
     * Write-in items (identification / matching) for the printable sheet,
     * numbered continuously after the bubble items. Display only — the correct
     * answers live in the frozen snapshot.
     *
     * @return array<int, array{n:int,type:string}>
     */
    private function writtenItems(Test $test, int $offset): array
    {
        $questions = $test->testQuestions()
            ->with('question')
            ->orderBy('order')
            ->get()
            ->map(fn ($tq) => $tq->question)
            ->filter(fn ($q) => $q && in_array($q->question_type, self::WRITE_TYPES, true))
            ->values();

        $out = [];
        foreach ($questions as $i => $q) {
            $out[] = [
                'n' => $offset + $i + 1,
                'type' => in_array($q->question_type, ['matching', 'match'], true) ? 'matching' : 'identification',
            ];
        }

        return $out;
    }

    /**
     * Per-student rows for the manual record page: sheet token, item count, and
     * the current graded result (with per-item marks) to pre-fill for editing.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recordRoster(Test $test, Section $section): array
    {
        return $this->enrolledStudents((int) $section->id)->map(function ($s) use ($test, $section) {
            $sheet = $this->snapshots->forStudent($test, (int) $s->id, (int) $section->id);
            $result = OmrResult::with('items')->where('omr_sheet_id', $sheet->id)->first();
            $bubbleCount = (int) $sheet->item_count;

            $marks = [];
            $writtenMarks = [];
            if ($result) {
                foreach ($result->items as $it) {
                    /** @var OmrItemResult $it */
                    if ($it->item_number <= $bubbleCount) {
                        $marks[$it->item_number] = $it->marked ? explode(',', $it->marked) : [];
                    } else {
                        $writtenMarks[$it->item_number] = $it->marked ?? '';
                    }
                }
            }

            return [
                'name' => $this->fullName($s),
                'student_number' => $s->student_number,
                'sheet_token' => $this->tokens->sheetToken($sheet->token, $sheet->layout_version),
                'item_count' => $bubbleCount,
                'written_items' => array_map(
                    fn ($k) => ['n' => $k['n'], 'type' => $k['type']],
                    $sheet->written_key ?? []
                ),
                'graded' => (bool) $result,
                'raw_score' => $result?->raw_score,
                'max_score' => (int) $sheet->max_score,
                'percentage' => $result ? (float) $result->percentage : null,
                'marks' => $marks,
                'written_marks' => $writtenMarks,
            ];
        })->all();
    }

    private function enrolledStudents(int $sectionId): Collection
    {
        return DB::table('student_enrollments as se')
            ->join('students as s', 's.id', '=', 'se.student_id')
            ->where('se.section_id', $sectionId)
            ->where('se.status', 'enrolled')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get(['s.id', 's.first_name', 's.middle_name', 's.last_name', 's.suffix', 's.student_number', 's.lrn']);
    }

    private function objectiveItemCount(Test $test): int
    {
        return $test->testQuestions()
            ->whereHas('question', fn ($q) => $q->whereIn('question_type', self::BUBBLE_TYPES))
            ->count();
    }

    private function fullName(object $s): string
    {
        $rest = trim(implode(' ', array_filter([$s->first_name, $s->middle_name, $s->suffix])));

        return trim($s->last_name.($rest !== '' ? ', '.$rest : ''));
    }

    private function gradeLabel(Section $section, ?string $educationLevel): string
    {
        $level = mb_strtolower((string) $educationLevel);
        $higher = str_contains($level, 'grad') || str_contains($level, 'under') || str_contains($level, 'college') || str_contains($level, 'higher');
        $prefix = $higher ? 'Year' : 'Grade';

        return trim($prefix.' '.$section->year_level.' – '.$section->name);
    }

    private function schoolYear(int $schoolId, ?int $academicYearId): ?string
    {
        if ($academicYearId && ($name = AcademicYear::where('id', $academicYearId)->value('name'))) {
            return $name;
        }

        return AcademicYear::where('school_id', $schoolId)->where('is_active', true)->value('name')
            ?? AcademicYear::where('school_id', $schoolId)->value('name');
    }
}
