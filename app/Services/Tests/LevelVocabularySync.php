<?php

namespace App\Services\Tests;

use App\Models\AcademicLevel;
use App\Models\EducationNode;
use App\Support\LevelName;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the academic_levels vocabulary in step with the admin education tree so
 * the tree is the single source of truth for levels: every OFFERED terminal
 * level node (a leaf of type level/stage — Toddler, Nursery, Grade 3, …) gets a
 * matching academic_levels row per school. Additive and idempotent — it never
 * deletes (questions may already be tagged with a level whose node was later
 * un-offered). Tracks/strands/program_types are navigation, not levels, so they
 * are skipped; questions are still tagged by grade/year.
 */
class LevelVocabularySync
{
    /** Node types that represent a taggable level (vs track/strand/program_type). */
    private const LEVEL_TYPES = [EducationNode::TYPE_LEVEL, EducationNode::TYPE_STAGE];

    /** Sync every school; returns the number of academic_levels rows created. */
    public function syncAllSchools(): int
    {
        $created = 0;

        foreach (DB::table('schools')->pluck('id') as $schoolId) {
            $created += $this->syncForSchool((int) $schoolId);
        }

        return $created;
    }

    /** Provision any missing levels for one school; returns rows created. */
    public function syncForSchool(int $schoolId): int
    {
        $nodes = EducationNode::query()
            ->where('is_active', true)
            ->where('is_offered', true)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get(['id', 'name', 'node_type', 'parent_id', 'order_index']);

        $byId = $nodes->keyBy('id');

        $hasChildren = [];
        foreach ($nodes as $n) {
            if ($n->parent_id !== null) {
                $hasChildren[$n->parent_id] = true;
            }
        }

        $existing = AcademicLevel::where('school_id', $schoolId)
            ->pluck('name')
            ->mapWithKeys(fn ($name) => [LevelName::key($name) => true])
            ->all();

        $created = 0;

        foreach ($nodes as $node) {
            if (! in_array($node->node_type, self::LEVEL_TYPES, true)) {
                continue;
            }
            if (! empty($hasChildren[$node->id])) {
                continue; // only terminal levels are taggable
            }

            $key = LevelName::key($node->name);
            if ($key === null || isset($existing[$key])) {
                continue;
            }

            AcademicLevel::create([
                'school_id' => $schoolId,
                'name' => LevelName::display($node->name),
                'type' => $this->typeForRoot($node, $byId),
                'sequence_order' => (int) ($node->order_index ?? 0),
            ]);

            $existing[$key] = true;
            $created++;
        }

        return $created;
    }

    /**
     * Vocabulary type is decided by the node's root ancestor: Basic Education →
     * basic, Training → training, everything else (undergrad/grad/…) → higher.
     */
    private function typeForRoot(EducationNode $node, $byId): string
    {
        $cursor = $node;
        while ($cursor->parent_id !== null && $byId->has($cursor->parent_id)) {
            $cursor = $byId->get($cursor->parent_id);
        }

        $root = mb_strtolower((string) $cursor->name);

        return match (true) {
            str_contains($root, 'basic') => 'basic',
            str_contains($root, 'training') => 'training',
            default => 'higher',
        };
    }
}
