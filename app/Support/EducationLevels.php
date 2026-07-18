<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Helpers for reading the admin "Education Structure Tree"
 * (education_nodes) — the single source of truth for which levels a school
 * offers and how grades/years are named.
 */
class EducationLevels
{
    /** Offered top-level levels (Basic Education, Undergraduate, …). */
    public static function offeredRoots(): Collection
    {
        return DB::table('education_nodes')
            ->whereNull('parent_id')
            ->where('is_offered', 1)
            ->where('is_active', 1)
            ->orderBy('order_index')
            ->get(['id', 'name']);
    }

    /**
     * Root ids a user is scoped to (`user_education_scopes`). An EMPTY array means
     * unrestricted — that is the default for every user with no rows, so scoping can
     * be introduced without locking anyone out.
     *
     * @return array<int, int>
     */
    public static function scopeRootIds(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return DB::table('user_education_scopes')
            ->where('user_id', $userId)
            ->pluck('education_node_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Offered roots this user may see — all of them when the user has no scope rows.
     * Use for the level tabs AND for what "All Levels" is allowed to span, so a
     * scoped user can never reach another level's data by dropping the ?level param.
     */
    public static function offeredRootsFor(?int $userId): Collection
    {
        $scoped = self::scopeRootIds($userId);
        $roots = self::offeredRoots();

        return $scoped === [] ? $roots : $roots->whereIn('id', $scoped)->values();
    }

    public static function isBasic(?string $name): bool
    {
        return $name !== null && str_contains(strtolower($name), 'basic');
    }

    /**
     * Levels limited to ONE open/active term per academic year — Basic Education
     * and Undergraduate. Every other level (Post-Bacc, Graduate, Post-Doctoral,
     * Training, …) may have unlimited concurrent active terms in the same year.
     */
    public static function isLimited(?string $name): bool
    {
        if ($name === null) {
            return false;
        }
        $n = strtolower($name);

        return self::isBasic($name) || str_contains($n, 'undergrad');
    }

    /**
     * Grade levels offered under Basic Education, as [yearLevel => label],
     * e.g. ['1' => 'Grade 1', … '12' => 'Grade 12']. Numbers are read from node
     * names like "Grade 7" / "Grade 11 (Core)", deduped and sorted — so every
     * ticked grade appears whether or not any student is enrolled in it.
     */
    public static function basicGradeOptions(): array
    {
        $root = self::offeredRoots()->first(fn ($r) => self::isBasic($r->name));
        if (! $root) {
            return [];
        }

        $all = DB::table('education_nodes')
            ->where('is_active', 1)
            ->get(['id', 'name', 'parent_id', 'is_offered'])
            ->keyBy('id');

        $grades = [];
        foreach ($all as $node) {
            if (! $node->is_offered) {
                continue;
            }
            if (! self::descendsFrom((int) $node->id, (int) $root->id, $all)) {
                continue;
            }
            if (preg_match('/grade\s*(\d+)/i', (string) $node->name, $m)) {
                $grades[(int) $m[1]] = true;
            }
        }

        ksort($grades);

        $options = [];
        foreach (array_keys($grades) as $g) {
            $options[(string) $g] = 'Grade '.$g;
        }

        return $options;
    }

    /**
     * Top-most offered stage groups under Basic Education (Preschool,
     * Elementary, Junior High, Senior High …) in tree order, as objects with
     * ->id and ->name — the shape <x-table.level-tabs> expects. Stages nested
     * inside another offered stage (Grade 1 under Elementary, strand-scoped
     * Grade 11s, …) are represented by their top-most stage ancestor; pair
     * with descendantIds() to match data mapped anywhere in a group's subtree.
     * Empty collection when Basic Education is not offered.
     */
    public static function basicStageGroups(): Collection
    {
        $root = self::offeredRoots()->first(fn ($r) => self::isBasic($r->name));
        if (! $root) {
            return collect();
        }

        $byParent = DB::table('education_nodes')
            ->where('is_active', 1)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get(['id', 'name', 'parent_id', 'node_type', 'is_offered'])
            ->groupBy('parent_id');

        $groups = collect();
        $walk = function ($parentId) use (&$walk, $byParent, &$groups) {
            foreach ($byParent->get($parentId, collect()) as $child) {
                if ($child->node_type === 'stage' && $child->is_offered) {
                    // Offered stage = a tab group; its subtree belongs to it.
                    $groups->push((object) ['id' => (int) $child->id, 'name' => $child->name]);
                } else {
                    // Non-stage (track/strand) or non-offered stage: keep
                    // descending so offered stages deeper down still surface.
                    $walk($child->id);
                }
            }
        };
        $walk($root->id);

        return $groups;
    }

    /**
     * Offered, active stage nodes anywhere under a node (excluding the node
     * itself), in tree order — e.g. the grade levels inside a
     * basicStageGroups() group (Elementary → Grade 1..6). Plain ->id/->name
     * objects; names repeat when the same grade exists under several strands.
     */
    public static function stageDescendants(int $nodeId): Collection
    {
        if (! $nodeId) {
            return collect();
        }

        $byParent = DB::table('education_nodes')
            ->where('is_active', 1)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get(['id', 'name', 'parent_id', 'node_type', 'is_offered'])
            ->groupBy('parent_id');

        $out = collect();
        $walk = function ($parentId) use (&$walk, $byParent, &$out) {
            foreach ($byParent->get($parentId, collect()) as $child) {
                if ($child->node_type === 'stage' && $child->is_offered) {
                    $out->push((object) ['id' => (int) $child->id, 'name' => $child->name]);
                }
                $walk($child->id);
            }
        };
        $walk($nodeId);

        return $out;
    }

    /**
     * Bucket the school's "academic levels" (Kinder, Grade 1, … — rows that
     * carry NO education_node foreign key) into the offered Basic-Ed stage
     * groups they belong to, so a page keyed on academic_levels can still use
     * the education-level tabs. Bridges the two tables by name, since they share
     * no key: by grade number ("Grade N") first, then, for pre-grade levels, by
     * loose token containment so "Kinder" still lands under the tree's
     * "Kindergarten". Returns [academicLevelId => stageGroupId]; levels that
     * match no offered stage are omitted (they only ever show under "All").
     * Empty when Basic Education defines no stage groups.
     *
     * @param  Collection<int, object>  $levels  objects with ->id and ->name
     * @return array<int, int>
     */
    public static function bucketBasicLevels(Collection $levels): array
    {
        $groups = self::basicStageGroups();
        if ($groups->isEmpty()) {
            return [];
        }

        // Index each group's offered stage subtree: grade numbers and, for
        // pre-grade stages, their normalized name tokens. First writer wins —
        // SHS strand duplicates ("Grade 11" × N) all resolve to the same group.
        $numberToGroup = [];
        $tokenToGroup = [];
        foreach ($groups as $g) {
            foreach (self::stageDescendants((int) $g->id) as $node) {
                if (preg_match('/grade\s*(\d+)/i', (string) $node->name, $m)) {
                    $numberToGroup[(int) $m[1]] ??= (int) $g->id;
                } elseif (($token = self::normalizeLevelToken($node->name)) !== '') {
                    $tokenToGroup[$token] ??= (int) $g->id;
                }
            }
        }

        $map = [];
        foreach ($levels as $lvl) {
            $name = (string) $lvl->name;

            if (preg_match('/grade\s*(\d+)/i', $name, $m) && isset($numberToGroup[(int) $m[1]])) {
                $map[(int) $lvl->id] = $numberToGroup[(int) $m[1]];

                continue;
            }

            $token = self::normalizeLevelToken($name);
            if ($token === '') {
                continue;
            }
            foreach ($tokenToGroup as $word => $gid) {
                if (str_contains($word, $token) || str_contains($token, $word)) {
                    $map[(int) $lvl->id] = $gid;
                    break;
                }
            }
        }

        return $map;
    }

    /** Lowercased, alphanumeric-only form of a level name for loose matching. */
    protected static function normalizeLevelToken(string $name): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', strtolower($name));
    }

    /**
     * A node's own id plus every descendant id (the whole subtree) — the
     * counterpart of ancestorIds(). Returns [] for a null/0 node.
     *
     * @return int[]
     */
    public static function descendantIds(?int $nodeId): array
    {
        if (! $nodeId) {
            return [];
        }

        $byParent = DB::table('education_nodes')->get(['id', 'parent_id'])->groupBy('parent_id');

        $ids = [];
        $stack = [$nodeId];
        while ($stack) {
            $cur = (int) array_pop($stack);
            $ids[] = $cur;
            foreach ($byParent->get($cur, collect()) as $child) {
                $stack[] = $child->id;
            }
        }

        return $ids;
    }

    /**
     * Year levels offered anywhere under a higher-education level's subtree, as
     * [yearLevel => label], e.g. ['1' => 'Year 1', … '4' => 'Year 4']. Numbers
     * are read from node names like "Year 1" / "year 4" (deduped + sorted), so
     * every year level set in the education tree appears whether or not any
     * student is enrolled in it. Returns [] when the level defines no year nodes.
     */
    public static function yearLevelOptions(int $rootId): array
    {
        if ($rootId <= 0) {
            return [];
        }

        $all = DB::table('education_nodes')
            ->where('is_active', 1)
            ->get(['id', 'name', 'parent_id', 'is_offered'])
            ->keyBy('id');

        $years = [];
        foreach ($all as $node) {
            if (! $node->is_offered) {
                continue;
            }
            if (! self::descendsFrom((int) $node->id, $rootId, $all)) {
                continue;
            }
            if (preg_match('/year\s*(\d+)/i', (string) $node->name, $m)) {
                $years[(int) $m[1]] = true;
            }
        }

        ksort($years);

        $options = [];
        foreach (array_keys($years) as $y) {
            $options[(string) $y] = 'Year '.$y;
        }

        return $options;
    }

    /**
     * Map of every education_node id => its top-level root id, so an enrollment
     * (via education_node_id or its program's node) can be bucketed by level.
     *
     * @return array<int,int|null>
     */
    public static function nodeRootMap(): array
    {
        $all = DB::table('education_nodes')->get(['id', 'parent_id'])->keyBy('id');

        $rootOf = [];
        foreach ($all as $id => $node) {
            $cur = $node;
            for ($i = 0; $i < 32 && $cur && $cur->parent_id; $i++) {
                $cur = $all[$cur->parent_id] ?? null;
            }
            $rootOf[$id] = $cur?->id;
        }

        return $rootOf;
    }

    /**
     * A node's own id plus every ancestor id up to the root — so a fee scoped to
     * a parent level (e.g. "Basic Education") still applies to a leaf enrolment
     * node (e.g. "Grade 5"). Returns [] for a null/0 node.
     *
     * @return int[]
     */
    public static function ancestorIds(?int $nodeId): array
    {
        if (! $nodeId) {
            return [];
        }

        $all = DB::table('education_nodes')->get(['id', 'parent_id'])->keyBy('id');

        $ids = [];
        $cur = $all[$nodeId] ?? null;
        for ($i = 0; $i < 32 && $cur; $i++) {
            $ids[] = (int) $cur->id;
            $cur = $cur->parent_id ? ($all[$cur->parent_id] ?? null) : null;
        }

        return $ids;
    }

    /** Whether $nodeId is the same as, or a descendant of, $ancestorId. */
    protected static function descendsFrom(int $nodeId, int $ancestorId, Collection $all): bool
    {
        $cur = $all[$nodeId] ?? null;
        for ($i = 0; $i < 32 && $cur; $i++) {
            if ((int) $cur->id === $ancestorId) {
                return true;
            }
            $cur = $cur->parent_id ? ($all[$cur->parent_id] ?? null) : null;
        }

        return false;
    }
}
