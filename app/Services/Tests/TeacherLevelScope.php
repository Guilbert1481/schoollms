<?php

namespace App\Services\Tests;

use App\Support\EducationLevels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Level-tab scoping shared by the teacher's own-data list pages (Test
 * Management, Question Bank): which education roots a teacher works under and
 * how the per-school academic_levels vocabulary maps back to those roots.
 *
 * Extracted from TestManagementService so every teacher list page derives its
 * tabs the same way instead of re-implementing the resolution rules.
 */
class TeacherLevelScope
{
    public function __construct(private LevelTreeResolver $levelTree) {}

    /** Id of the offered Basic Education root, or 0 when none is offered. */
    public function basicRootId(Collection $roots): int
    {
        $basic = $roots->first(fn ($r) => EducationLevels::isBasic($r->name));

        return $basic ? (int) $basic->id : 0;
    }

    /**
     * Root ids reachable from the sections this teacher advises or teaches:
     * program → node → root for higher ed, the Basic root for basic-ed terms.
     */
    public function sectionRoots(int $userId, int $schoolId, Collection $roots): Collection
    {
        $nodeRoot = EducationLevels::nodeRootMap();
        $basicRootId = $this->basicRootId($roots);

        return DB::table('sections as s')
            ->join('terms as t', 't.id', '=', 's.term_id')
            ->leftJoin('programs as p', 'p.id', '=', 's.program_id')
            ->where('s.school_id', $schoolId)
            ->where(function ($q) use ($userId) {
                $q->where('s.adviser_id', $userId)
                    ->orWhereExists(fn ($sub) => $sub->select(DB::raw(1))
                        ->from('classes as c')
                        ->whereColumn('c.section_id', 's.id')
                        ->where('c.teacher_id', $userId));
            })
            ->get(['t.education_level', 'p.education_node_id'])
            ->map(function ($s) use ($nodeRoot, $basicRootId) {
                if ($s->education_node_id) {
                    return $nodeRoot[$s->education_node_id] ?? null;
                }

                return $s->education_level === 'basic_ed' ? ($basicRootId ?: null) : null;
            })
            ->filter()
            ->values();
    }

    /**
     * academic_level id => root node id, built from the same resolver the Test
     * Builder used to tag the levels — so display agrees with authoring.
     * A level covered by several roots keeps the first root in tree order.
     *
     * @return array<int, int>
     */
    public function levelToRootMap(int $schoolId, Collection $roots): array
    {
        $byNode = $this->levelTree->levelsByNode($schoolId);

        $map = [];
        foreach ($roots as $root) {
            foreach ($byNode[$root->id] ?? [] as $level) {
                $map[(int) $level['id']] ??= (int) $root->id;
            }
        }

        return $map;
    }
}
