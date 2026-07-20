<?php

namespace App\Services\Academics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registrar Sectioning Workbench: place enrolled-but-unsectioned basic-ed
 * students into their grade's published sections for a term.
 *
 * student_enrollments.section_id is the single source of truth for a basic-ed
 * student's section — rosters, attendance and the gradebook all resolve
 * through it. Auto-distribution only ever produces a PROPOSAL; nothing is
 * written until the registrar applies it.
 */
class SectioningService
{
    private const SECTIONABLE = ['enrolled', 'provisionally_enrolled'];

    /**
     * Per-grade picture for the page: unsectioned students and published
     * sections (with occupancy) of every grade that has either — objects with
     * node_id, node_name, students (unsectioned), sections (occupancy).
     */
    public function overview(int $schoolId, int $termId): Collection
    {
        $students = DB::table('student_enrollments as e')
            ->join('students as st', 'st.id', '=', 'e.student_id')
            ->join('education_nodes as n', 'n.id', '=', 'e.education_node_id')
            ->where('e.school_id', $schoolId)
            ->where('e.term_id', $termId)
            ->whereIn('e.status', self::SECTIONABLE)
            ->whereNull('e.section_id')
            ->whereNotNull('e.education_node_id')
            ->orderBy('st.last_name')->orderBy('st.first_name')
            ->get([
                'e.id as enrollment_id', 'e.education_node_id', 'n.name as node_name',
                'st.id as student_id', 'st.first_name', 'st.last_name', 'e.status',
            ]);

        $sections = $this->publishedGradeSections($schoolId, $termId);

        $nodeIds = $students->pluck('education_node_id')
            ->merge($sections->pluck('education_node_id'))
            ->map(fn ($i) => (int) $i)->unique();

        $nodeNames = DB::table('education_nodes')->whereIn('id', $nodeIds)->pluck('name', 'id');

        return $nodeIds->sort()->values()->map(fn ($nodeId) => (object) [
            'node_id' => $nodeId,
            'node_name' => (string) ($nodeNames[$nodeId] ?? "Node {$nodeId}"),
            'students' => $students->where('education_node_id', $nodeId)->values(),
            'sections' => $sections->where('education_node_id', $nodeId)->values(),
        ]);
    }

    /**
     * Propose section placements for one grade's unsectioned students.
     * Fills the section with the most remaining seats first (keeps sizes
     * balanced), ordering students per the strategy.
     *
     * @return array{placements: array<int,int>, leftover: array<int>, strategy: string}
     */
    public function plan(int $schoolId, int $termId, int $nodeId, string $strategy): array
    {
        $grade = $this->overview($schoolId, $termId)->firstWhere('node_id', $nodeId);
        if (! $grade || $grade->students->isEmpty()) {
            return ['placements' => [], 'leftover' => [], 'strategy' => $strategy];
        }

        $students = match ($strategy) {
            'random' => $grade->students->shuffle(),
            default => $grade->students, // 'alphabetical' — overview() is already last-name ordered
        };

        // remaining seats per section (capacity 0 = unlimited).
        $open = $grade->sections->map(fn ($s) => (object) [
            'id' => (int) $s->id,
            'remaining' => (int) $s->capacity > 0 ? max(0, (int) $s->capacity - (int) $s->taken) : PHP_INT_MAX,
        ])->filter(fn ($s) => $s->remaining > 0)->values()->all();

        $placements = [];
        $leftover = [];
        foreach ($students as $stu) {
            usort($open, fn ($a, $b) => $b->remaining <=> $a->remaining);
            $target = $open[0] ?? null;
            if (! $target || $target->remaining < 1) {
                $leftover[] = (int) $stu->enrollment_id;

                continue;
            }
            $placements[(int) $stu->enrollment_id] = $target->id;
            $target->remaining--;
        }

        return ['placements' => $placements, 'leftover' => $leftover, 'strategy' => $strategy];
    }

    /**
     * Apply placements (enrollment_id => section_id). Every row is re-guarded:
     * tenant, term, grade match, still unsectioned, and section capacity —
     * so a stale or crafted form can never mis-place a student.
     *
     * @param  array<int,int>  $placements
     * @return int number of students sectioned
     */
    public function apply(int $schoolId, int $termId, array $placements): int
    {
        if ($placements === []) {
            return 0;
        }

        return DB::transaction(function () use ($schoolId, $termId, $placements) {
            $sections = $this->publishedGradeSections($schoolId, $termId)->keyBy('id');

            $enrollments = DB::table('student_enrollments')
                ->where('school_id', $schoolId)
                ->where('term_id', $termId)
                ->whereIn('id', array_keys($placements))
                ->whereIn('status', self::SECTIONABLE)
                ->whereNull('section_id')
                ->lockForUpdate()
                ->get(['id', 'education_node_id'])
                ->keyBy('id');

            $added = [];
            foreach ($placements as $enrollmentId => $sectionId) {
                $enrollment = $enrollments[(int) $enrollmentId] ?? null;
                $section = $sections[(int) $sectionId] ?? null;

                if (! $enrollment) {
                    throw ValidationException::withMessages([
                        'placements' => 'A selected student is no longer unsectioned — reload and try again.',
                    ]);
                }
                if (! $section) {
                    throw ValidationException::withMessages([
                        'placements' => 'Target section is not a published section of this term.',
                    ]);
                }
                if ((int) $enrollment->education_node_id !== (int) $section->education_node_id) {
                    throw ValidationException::withMessages([
                        'placements' => "Section \"{$section->name}\" is not a section of that student's grade level.",
                    ]);
                }

                $added[(int) $sectionId] = ($added[(int) $sectionId] ?? 0) + 1;
                if ((int) $section->capacity > 0
                    && (int) $section->taken + $added[(int) $sectionId] > (int) $section->capacity) {
                    throw ValidationException::withMessages([
                        'placements' => "Section \"{$section->name}\" would exceed its capacity of {$section->capacity}.",
                    ]);
                }
            }

            foreach ($placements as $enrollmentId => $sectionId) {
                DB::table('student_enrollments')->where('id', (int) $enrollmentId)
                    ->update(['section_id' => (int) $sectionId, 'updated_at' => now()]);
            }

            return count($placements);
        });
    }

    /** Published grade-level sections of the term with current occupancy. */
    private function publishedGradeSections(int $schoolId, int $termId): Collection
    {
        return DB::table('sections as s')
            ->leftJoin('student_enrollments as e', function ($join) {
                $join->on('e.section_id', '=', 's.id')
                    ->whereIn('e.status', self::SECTIONABLE);
            })
            ->where('s.school_id', $schoolId)
            ->where('s.term_id', $termId)
            ->where('s.status', 'published')
            ->whereNotNull('s.education_node_id')
            ->groupBy('s.id', 's.education_node_id', 's.name', 's.capacity')
            ->orderBy('s.name')
            ->get([
                's.id', 's.education_node_id', 's.name', 's.capacity',
                DB::raw('COUNT(e.id) as taken'),
            ]);
    }
}
