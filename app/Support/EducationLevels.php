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

    public static function isBasic(?string $name): bool
    {
        return $name !== null && str_contains(strtolower($name), 'basic');
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
