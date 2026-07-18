<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceSummaryService;
use Illuminate\Http\Request;

/**
 * Teacher → Attendance → Summary. Read-only historical view over the marks the
 * Daily tab captures: pick an academic year, grade level, section, source and
 * duration, and see the section trend plus a per-student breakdown.
 *
 * Every selection is re-derived from the teacher's own sections rather than
 * trusted from the query string, so a crafted section_id cannot reach another
 * teacher's roster — the same ownership guard AttendanceController::store uses.
 */
class AttendanceSummaryController extends Controller
{
    public function index(Request $request, AttendanceSummaryService $summaries)
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;

        $sections = $summaries->sectionsFor($user->id, $schoolId);

        if ($sections->isEmpty()) {
            return view('teacher.attendance.summary', [
                'sections' => $sections,
                'years' => collect(),
                'levels' => collect(),
                'sectionOptions' => collect(),
                'classes' => collect(),
                'sources' => collect(),
                'durations' => AttendanceSummaryService::DURATIONS,
                'selected' => null,
                'summary' => null,
            ]);
        }

        // Academic year — default to the most recently started one the teacher
        // has a section in, so the page opens on current work, not year one.
        $years = $sections
            ->filter(fn ($s) => $s->academic_year_id !== null)
            ->unique('academic_year_id')
            ->sortByDesc('term_start')
            ->map(fn ($s) => (object) [
                'id' => (int) $s->academic_year_id,
                'name' => $s->academic_year_name ?: 'AY '.$s->academic_year_id,
            ])
            ->values();

        $yearId = (int) $request->query('academic_year_id');
        if (! $years->contains('id', $yearId)) {
            $yearId = (int) ($years->first()->id ?? 0);
        }

        $inYear = $sections->where('academic_year_id', $yearId)->values();

        // Grade level — labelled per education level ("Grade 5" vs "Year 3").
        $levels = $inYear
            ->unique('year_level')
            ->sortBy('year_level')
            ->map(fn ($s) => (object) ['value' => (int) $s->year_level, 'label' => $s->level_label])
            ->values();

        $level = $request->filled('year_level') ? (int) $request->query('year_level') : null;
        if ($level === null || ! $levels->contains('value', $level)) {
            $level = (int) ($levels->first()->value ?? 0);
        }

        $sectionOptions = $inYear->where('year_level', $level)->values();

        $sectionId = (int) $request->query('section_id');
        $section = $sectionOptions->firstWhere('id', $sectionId) ?: $sectionOptions->first();

        if (! $section) {
            $section = $inYear->first();
            $sectionOptions = $inYear;
        }

        // Source — homeroom (daily) only when this teacher advises the section.
        $classes = $summaries->classesFor($user->id, $schoolId, (int) $section->id);
        $sources = collect();
        if ($section->is_adviser) {
            $sources->push((object) ['value' => AttendanceSummaryService::SOURCE_HOMEROOM, 'label' => 'Homeroom · daily']);
        }
        if ($classes->isNotEmpty()) {
            $sources->push((object) ['value' => AttendanceSummaryService::SOURCE_ALL, 'label' => 'All subjects']);
            foreach ($classes as $c) {
                $sources->push((object) ['value' => (string) $c->id, 'label' => $c->label]);
            }
        }

        $source = (string) $request->query('source', '');
        if (! $sources->contains('value', $source)) {
            $source = (string) ($sources->first()->value ?? AttendanceSummaryService::SOURCE_HOMEROOM);
        }

        $duration = (string) $request->query('duration', AttendanceSummaryService::DURATION_MONTHLY);
        if (! array_key_exists($duration, AttendanceSummaryService::DURATIONS)) {
            $duration = AttendanceSummaryService::DURATION_MONTHLY;
        }

        return view('teacher.attendance.summary', [
            'sections' => $sections,
            'years' => $years,
            'levels' => $levels,
            'sectionOptions' => $sectionOptions,
            'classes' => $classes,
            'sources' => $sources,
            'durations' => AttendanceSummaryService::DURATIONS,
            'selected' => (object) [
                'academic_year_id' => $yearId,
                'year_level' => $level,
                'section' => $section,
                'source' => $source,
                'duration' => $duration,
            ],
            'summary' => $summaries->summarize($section, $source, $duration, $classes, $schoolId),
        ]);
    }
}
