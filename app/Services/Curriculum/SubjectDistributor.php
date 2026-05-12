<?php

namespace App\Services\Curriculum;

class SubjectDistributor
{
    /**
     * Distribute subjects into terms/years/summer for a program.
     *
     * @param array $subjects [ ['code'=>..., 'name'=>..., 'units'=>..., 'prerequisites'=>[...], ...], ... ]
     * @param int $years
     * @param int $termsPerYear (2, 3, or 4)
     * @param array $summerConfig [
     *   'enabled' => bool,
     *   'max_subjects' => int|null,
     *   'max_units' => float|null,
     *   'duration' => 'yearly'|'program'|int|null, // int = year number
     *   'manual' => [year => [subject_code, ...], ...] // optional manual summer assignments
     * ]
     * @return array [ year => [ term => [subjects...] ] ]
     */
    public function distribute(array $subjects, int $years, int $termsPerYear, array $summerConfig = []): array
    {
        // 1. Topological sort by prerequisites
        $sorted = $this->topoSort($subjects);
        // 2. Distribute into terms
        $plan = $this->autoDistribute($sorted, $years, $termsPerYear, $summerConfig);
        return $plan;
    }

    protected function topoSort(array $subjects): array
    {
        // Kahn's algorithm
        $byCode = collect($subjects)->keyBy('code');
        $inDegree = [];
        $graph = [];
        foreach ($subjects as $s) {
            $inDegree[$s['code']] = count($s['prerequisites'] ?? []);
            foreach ($s['prerequisites'] ?? [] as $pre) {
                $graph[$pre][] = $s['code'];
            }
        }
        $queue = collect($subjects)->filter(fn($s) => $inDegree[$s['code']] === 0)->pluck('code')->all();
        $result = [];
        while ($queue) {
            $code = array_shift($queue);
            $result[] = $byCode[$code];
            foreach ($graph[$code] ?? [] as $next) {
                $inDegree[$next]--;
                if ($inDegree[$next] === 0) $queue[] = $next;
            }
        }
        if (count($result) !== count($subjects)) {
            throw new \Exception('Cycle detected in prerequisites');
        }
        return $result;
    }

    protected function autoDistribute(array $subjects, int $years, int $termsPerYear, array $summerConfig): array
    {
        $plan = [];
        $summerEnabled = $summerConfig['enabled'] ?? false;
        $maxSummerSubjects = $summerConfig['max_subjects'] ?? null;
        $maxSummerUnits = $summerConfig['max_units'] ?? null;
        $summerManual = $summerConfig['manual'] ?? [];
        $duration = $summerConfig['duration'] ?? null;
        $subjectPool = collect($subjects);
        $yearTerms = range(1, $years);
        $termNums = range(1, $termsPerYear);
        // 1. Manual summer assignments
        $summerAssigned = [];
        if ($summerEnabled && $summerManual) {
            foreach ($summerManual as $year => $codes) {
                $plan[$year]['S'] = [];
                foreach ($codes as $code) {
                    $s = $subjectPool->firstWhere('code', $code);
                    if ($s) {
                        $plan[$year]['S'][] = $s;
                        $summerAssigned[] = $code;
                    }
                }
            }
        }
        // 2. Auto-distribute remaining subjects
        $remaining = $subjectPool->filter(fn($s) => !in_array($s['code'], $summerAssigned))->values();
        $i = 0;
        foreach ($yearTerms as $year) {
            foreach ($termNums as $term) {
                $plan[$year][$term] = [];
            }
            // Summer auto-fill
            if ($summerEnabled && empty($plan[$year]['S'])) {
                $plan[$year]['S'] = [];
                $count = 0; $units = 0;
                while ($i < count($remaining)) {
                    $s = $remaining[$i];
                    if ($maxSummerSubjects && $count >= $maxSummerSubjects) break;
                    if ($maxSummerUnits && $units + $s['units'] > $maxSummerUnits) break;
                    $plan[$year]['S'][] = $s;
                    $count++; $units += $s['units'];
                    $i++;
                }
            }
            // Regular terms
            foreach ($termNums as $term) {
                $count = 0; $units = 0;
                while ($i < count($remaining)) {
                    $s = $remaining[$i];
                    $plan[$year][$term][] = $s;
                    $i++;
                    break; // 1 subject per slot for now (can be improved)
                }
            }
        }
        return $plan;
    }
}
