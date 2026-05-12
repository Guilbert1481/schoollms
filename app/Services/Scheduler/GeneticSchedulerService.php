<?php

namespace App\Services\Scheduler;

class GeneticSchedulerService
{
    public function __construct(private ScheduleGeneratorService $generator) {}

    public function optimize(array $payload, int $populationSize = 12, int $generations = 6): array
    {
        // Initial population by re-running generator
        $population = [];
        for ($i = 0; $i < $populationSize; $i++) {
            $r = $this->generator->generate($payload, 1);
            $population[] = $r['options'][0] ?? ['sessions' => [], 'score' => 0];
        }

        for ($g = 0; $g < $generations; $g++) {
            usort($population, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
            $elite = array_slice($population, 0, max(2, intdiv($populationSize, 4)));

            $children = [];
            while (count($children) + count($elite) < $populationSize) {
                $p1 = $elite[array_rand($elite)];
                $p2 = $elite[array_rand($elite)];
                $child = $this->crossover($p1, $p2);
                if (mt_rand(0, 100) < 25) $child = $this->mutate($child, $payload);
                $child['score'] = $this->generator->scoreSchedule(
                    $child['sessions'] ?? [],
                    $payload['weights'] ?? [],
                    $payload['time'] ?? []
                );
                $children[] = $child;
            }
            $population = array_merge($elite, $children);
        }

        usort($population, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
        $top = array_slice($population, 0, 3);

        // Collapse cards that look identical to the user (same score, session
        // count, conflict count). No point showing three carbon-copy options.
        $seen = [];
        $unique = [];
        foreach ($top as $opt) {
            $sig = sprintf(
                '%.4f|%d|%d',
                round((float) ($opt['score'] ?? 0), 4),
                count($opt['sessions']  ?? []),
                count($opt['conflicts'] ?? [])
            );
            if (isset($seen[$sig])) continue;
            $seen[$sig] = true;
            $unique[]   = $opt;
        }

        return [
            'options' => $unique ?: $top,
        ];
    }

    private function crossover(array $a, array $b): array
    {
        $sa = $a['sessions'] ?? [];
        $sb = $b['sessions'] ?? [];
        if (empty($sa)) return $b;
        if (empty($sb)) return $a;
        $cut = intdiv(min(count($sa), count($sb)), 2);
        return ['sessions' => array_merge(array_slice($sa, 0, $cut), array_slice($sb, $cut))];
    }

    private function mutate(array $child, array $payload): array
    {
        $days = $payload['time']['days_of_week'] ?? ['monday','tuesday','wednesday','thursday','friday'];
        $sessions = $child['sessions'] ?? [];
        if (empty($sessions)) return $child;
        $idx = array_rand($sessions);
        $sessions[$idx]['day_of_week'] = $days[array_rand($days)];
        $child['sessions'] = $sessions;
        return $child;
    }
}
