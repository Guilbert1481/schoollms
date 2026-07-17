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
    public function __construct(
        private OmrSheetTokenService $tokens,
        private OmrSheetSnapshotService $snapshots,
    ) {}

    /**
     * Sections with enrolled students for the print picker. A class-bound test
     * targets exactly its class's section, so scope to that one — otherwise the
     * picker lists every section in the school. Personal/no-class tests fall
     * back to all sections that have enrolled students.
     */
    public function sectionsForPicker(Test $test): Collection
    {
        $query = DB::table('student_enrollments as se')
            ->join('sections as sec', 'sec.id', '=', 'se.section_id')
            ->where('sec.school_id', $test->school_id)
            ->where('se.status', 'enrolled');

        if ($sectionId = $test->class?->section_id) {
            $query->where('sec.id', $sectionId);
        }

        return $query
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

        $items = $this->layoutItems($test);
        $layout = OmrLayout::regions($items);

        return [
            'schoolYear' => $this->schoolYear((int) $test->school_id, $meta?->academic_year_id),
            'profile' => SchoolProfile::where('school_id', $test->school_id)->first(),
            'gradeLabel' => $this->gradeLabel($section, $meta?->education_level),
            'itemCount' => count($items),
            'grid' => $layout['bubbles'],
            'writes' => $layout['writes'],
            'headers' => $layout['headers'],
            'bands' => $layout['bands'],
            'fiducials' => OmrLayout::fiducials(),
            'layoutVersion' => OmrLayout::VERSION,
            'regionHeight' => $layout['region_height_in'],
            'sheets' => $sheets,
        ];
    }

    /**
     * The test's OMR items ordered into the canonical section sequence and
     * numbered 1..N, so the printed sheet, the camera scanner, and the frozen
     * answer key (OmrSheetSnapshotService, which sequences identically) all agree
     * on which number is which question.
     *
     * @return array<int, array<string, mixed>>
     */
    public function layoutItems(Test $test): array
    {
        $raw = $test->testQuestions()
            ->with('question')
            ->orderBy('order')
            ->get()
            ->filter(fn ($tq) => $tq->question && OmrLayout::isSupported($tq->question->question_type))
            ->map(fn ($tq) => ['type' => $tq->question->question_type, 'order' => (int) $tq->order])
            ->values()
            ->all();

        return OmrLayout::sequence($raw, $test->print_seed);
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
