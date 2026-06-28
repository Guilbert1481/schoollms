<?php

namespace App\Support;

use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

/**
 * Centralised programme-label formatter used by Applicant Directory,
 * Billing & Payment Queue, etc. Keeps presentation consistent everywhere:
 *
 *   Basic Education : "Grade {N} - {Program} ({Modality})"
 *   Other levels    : "{Program} ({Modality})"
 */
class EnrollmentProgramLabel
{
    /** Cached map of education_node_id => root_id. */
    protected static ?array $nodeRootMap = null;

    public static function for(StudentEnrollment $enrollment): string
    {
        $programLbl = $enrollment->program?->name
            ?? $enrollment->educationNode?->name
            ?? '—';

        if ($enrollment->modality?->name) {
            $programLbl .= ' ('.$enrollment->modality->name.')';
        }

        if (self::isBasicEducation($enrollment)) {
            $yr = $enrollment->year_level;
            if ($yr !== null && $yr !== '' && $yr !== '—') {
                $yrLabel    = is_numeric($yr) ? 'Grade '.$yr : (string) $yr;
                $programLbl = $yrLabel.' - '.$programLbl;
            }
        }

        return $programLbl;
    }

    protected static function isBasicEducation(StudentEnrollment $enrollment): bool
    {
        $rootMap = self::nodeRootMap();

        $rootId = $rootMap[$enrollment->education_node_id ?? null]
            ?? $rootMap[$enrollment->program?->education_node_id ?? null]
            ?? null;

        if (! $rootId) {
            return false;
        }

        $rootName = DB::table('education_nodes')
            ->where('id', $rootId)
            ->value('name');

        return str_contains(strtolower((string) $rootName), 'basic');
    }

    protected static function nodeRootMap(): array
    {
        if (self::$nodeRootMap !== null) {
            return self::$nodeRootMap;
        }

        $all = DB::table('education_nodes')
            ->get(['id', 'parent_id'])
            ->keyBy('id');

        $rootOf = [];
        foreach ($all as $id => $node) {
            $cur = $node;
            for ($i = 0; $i < 32 && $cur && $cur->parent_id; $i++) {
                $cur = $all[$cur->parent_id] ?? null;
            }
            $rootOf[$id] = $cur?->id;
        }

        return self::$nodeRootMap = $rootOf;
    }
}
