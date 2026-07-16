<?php

namespace App\Services\Tests;

use App\Models\AcademicLevel;
use App\Models\EducationNode;
use Illuminate\Support\Collection;

/**
 * Bridges the admin education-structure tree (`education_nodes`) to the
 * per-school question-tag vocabulary (`academic_levels`) for the Test Builder's
 * "Assessment Levels" picker.
 *
 * The tree is a navigational structure (Basic Education → Elementary →
 * Grade 1..6; SHS → Academic Track → STEM; Undergraduate → BSED-Math → Year 1).
 * Questions, however, are tagged ONLY with `academic_levels` rows — the standard
 * vocabulary of Kinder, Grade 1-12, Year 1-5, Training, Review (see ADR-0006).
 * So the cascade is a nicer picker whose selection must resolve back to
 * academic_level ids, which the builder already submits as `academic_levels[]`
 * and filters the question bank by.
 *
 * Two shapes feed the view:
 *   - tree(): the active tree as nested arrays for the cascading dropdowns.
 *   - levelsByNode($schoolId): nodeId => [{id,name}] — the academic_levels a
 *     node covers, computed from its subtree with name-normalisation, climbing
 *     to the parent when a structural node (strand/track/program_type) carries
 *     no grade of its own (e.g. STEM → its SHS ancestor → Grade 11/12).
 */
class LevelTreeResolver
{
    /**
     * Active education-structure tree as nested arrays. Each node carries a
     * `drillable` flag: false once its children are the grade/year leaves
     * themselves (the checkbox picker handles those — no redundant dropdown).
     *
     * @return array<int, array<string, mixed>>
     */
    public function tree(): array
    {
        $nodes = $this->activeNodes();

        return $this->buildBranch($nodes, null);
    }

    /**
     * Map of every active node id => the academic_levels it covers, each as
     * ['id' => int, 'name' => string], ordered by the level's sequence.
     *
     * @return array<int, array<int, array{id:int,name:string}>>
     */
    public function levelsByNode(int $schoolId): array
    {
        $nodes = $this->activeNodes()->keyBy('id');

        // Standard per-school vocabulary, keyed by lowercased name for matching.
        $levelByName = AcademicLevel::where('school_id', $schoolId)
            ->orderBy('sequence_order')
            ->get(['id', 'name', 'sequence_order'])
            ->keyBy(fn ($l) => mb_strtolower($l->name));

        // parent_id => [childId, ...] for subtree walks. Roots (null parent) are
        // never looked up by parent, so skip them and avoid a null array offset.
        $childrenOf = [];
        foreach ($nodes as $n) {
            if ($n->parent_id !== null) {
                $childrenOf[$n->parent_id][] = $n->id;
            }
        }

        $map = [];
        foreach ($nodes as $id => $node) {
            $map[$id] = $this->resolveForNode($id, $nodes, $childrenOf, $levelByName);
        }

        return $map;
    }

    /* ------------------------------------------------------------------ */

    /**
     * Only nodes the school actually offers (is_offered) feed the picker — the
     * same rule the admin tree advertises ("Only checked items will appear") and
     * the enrollment cascade uses. A non-offered node also blocks its subtree:
     * its offered children point at an excluded parent, so buildBranch never
     * reaches them (correct — nothing under an unchecked level should show).
     */
    private function activeNodes(): Collection
    {
        return EducationNode::query()
            ->where('is_active', true)
            ->where('is_offered', true)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get(['id', 'name', 'node_type', 'parent_id']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBranch(Collection $nodes, ?int $parentId): array
    {
        return $nodes
            ->filter(fn ($n) => $n->parent_id === $parentId)
            ->map(function ($n) use ($nodes) {
                $children = $this->buildBranch($nodes, $n->id);

                return [
                    'id' => $n->id,
                    'name' => $n->name,
                    'node_type' => $n->node_type,
                    'children' => $children,
                    'drillable' => $this->isDrillable($children),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Drill into a node only when a child has its OWN children — a deeper
     * structural branch that actually needs a sub-dropdown. Leaf children never
     * warrant one: the mappable ones (Grade 6, Kinder) already show in the
     * grade/year picker, and the non-mappable ones (Toddler, Nursery) have no
     * questions. So Preschool → [Toddler, Nursery, Kindergarten] just offers
     * "Kinder" in the picker instead of a redundant Toddler/Nursery dropdown.
     *
     * @param  array<int, array<string, mixed>>  $children
     */
    private function isDrillable(array $children): bool
    {
        foreach ($children as $c) {
            if (! empty($c['children'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Levels covered by a node's subtree; if none (a structural node with no
     * grade of its own), climb to the parent until a mappable ancestor is
     * found or the root is reached.
     *
     * @return array<int, array{id:int,name:string}>
     */
    private function resolveForNode(int $nodeId, Collection $nodes, array $childrenOf, Collection $levelByName): array
    {
        $levels = $this->subtreeLevels($nodeId, $nodes, $childrenOf, $levelByName);

        $cursor = $nodeId;
        while (empty($levels) && ($parent = $nodes[$cursor]->parent_id ?? null) !== null) {
            $cursor = $parent;
            $levels = $this->subtreeLevels($cursor, $nodes, $childrenOf, $levelByName);
        }

        return $levels;
    }

    /**
     * Distinct academic levels named anywhere in a node's subtree, ordered by
     * the level's sequence.
     *
     * @return array<int, array{id:int,name:string}>
     */
    private function subtreeLevels(int $nodeId, Collection $nodes, array $childrenOf, Collection $levelByName): array
    {
        $found = [];
        $stack = [$nodeId];

        while ($stack) {
            $cur = array_pop($stack);
            $node = $nodes[$cur] ?? null;
            if (! $node) {
                continue;
            }

            $key = $this->normalise($node->name);
            if ($key !== null && $levelByName->has($key)) {
                $lvl = $levelByName->get($key);
                $found[$lvl->id] = ['id' => $lvl->id, 'name' => $lvl->name, 'seq' => $lvl->sequence_order];
            }

            foreach ($childrenOf[$cur] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        uasort($found, fn ($a, $b) => $a['seq'] <=> $b['seq']);

        return array_values(array_map(
            fn ($f) => ['id' => $f['id'], 'name' => $f['name']],
            $found
        ));
    }

    /**
     * Normalise an education-node name to an `academic_levels` name key
     * (lowercased), or null when the node carries no grade/year of its own.
     * Handles the known tree ↔ vocabulary mismatches:
     *   "Grade 11 (Core)" → "grade 11"; "Kindergarten" → "kinder";
     *   "year 4" → "year 4"; "Training"/"Review" pass through.
     */
    private function normalise(string $name): ?string
    {
        $n = trim($name);
        // Drop a trailing parenthetical qualifier: "Grade 11 (Core)" → "Grade 11".
        $n = (string) preg_replace('/\s*\([^)]*\)\s*$/', '', $n);
        $n = trim($n);

        if (preg_match('/^kinder/i', $n)) {
            return 'kinder';
        }
        if (preg_match('/^grade\s*(\d{1,2})$/i', $n, $m)) {
            return 'grade '.$m[1];
        }
        if (preg_match('/^year\s*(\d{1,2})$/i', $n, $m)) {
            return 'year '.$m[1];
        }
        if (preg_match('/^training$/i', $n)) {
            return 'training';
        }
        if (preg_match('/^review$/i', $n)) {
            return 'review';
        }

        return null;
    }
}
