<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RedistributeProgramSubjects extends Command
{
    protected $signature = 'program-subjects:redistribute
                            {--program= : Limit to a single program_id}
                            {--dry-run : Show planned changes without saving}';

    protected $description = 'Remove summer entries from program_subjects and re-balance subjects across sem 1 & 2 by category, prerequisite, and load.';

    /** Heavy categories that should not stack in one semester. */
    private array $weights = [
        'research'    => 10,
        'math'        => 9,
        'science'     => 9,
        'lab'         => 8,
        'literature'  => 7,
        'linguistics' => 7,
        'field_study' => 6,
        'prof_ed'     => 6,
        'language'    => 5,
        'elective'    => 4,
        'gec'         => 3,
        'pe'          => 2,
        'nstp'        => 2,
        'other'       => 4,
    ];

    public function handle(): int
    {
        $programs = DB::table('program_subjects')
            ->when($this->option('program'), fn($q,$p) => $q->where('program_id', $p))
            ->distinct()->pluck('program_id');

        if ($programs->isEmpty()) {
            $this->warn('No program_subjects rows found.');
            return self::SUCCESS;
        }

        foreach ($programs as $programId) {
            $this->info("\n=== Program {$programId} ===");
            $this->processProgram((int)$programId);
        }

        // Mark curricula as no-summer so the rest of the system stops scheduling it.
        if (! $this->option('dry-run') && Schema::hasColumn('curriculums', 'has_summer_term')) {
            $programIds = $programs->all();
            DB::table('curriculums')->whereIn('program_id', $programIds)->update([
                'has_summer_term' => 0,
                'updated_at' => now(),
            ]);
            $this->line("Disabled has_summer_term for ".count($programIds)." curriculum program(s).");
        }

        $this->info("\nDone.");
        return self::SUCCESS;
    }

    private function processProgram(int $programId): void
    {
        $years = DB::table('program_subjects')
            ->where('program_id', $programId)
            ->distinct()->orderBy('year_level')->pluck('year_level');

        // Cache prerequisite map: subject_id => [required_ids]
        $prereqs = DB::table('subject_prerequisites')
            ->get()->groupBy('subject_id')
            ->map(fn($g) => $g->pluck('prerequisite_subject_id')->all())
            ->all();

        // Track placement: subject_id => ['year'=>..,'sem'=>..]
        $placed = [];

        foreach ($years as $year) {
            $rows = DB::table('program_subjects as ps')
                ->join('subjects as s', 's.id', '=', 'ps.subject_id')
                ->where('ps.program_id', $programId)
                ->where('ps.year_level', $year)
                ->get(['ps.id','ps.subject_id','s.code','s.name']);

            $items = $rows->map(function ($r) {
                return [
                    'id'         => $r->id,
                    'subject_id' => (int)$r->subject_id,
                    'code'       => (string)$r->code,
                    'name'       => (string)$r->name,
                    'cat'        => $this->categorize((string)$r->code, (string)$r->name),
                ];
            })->all();

            // Sort heavy-first; stable inside category by code.
            usort($items, function ($a, $b) {
                $wa = $this->weights[$a['cat']] ?? 4;
                $wb = $this->weights[$b['cat']] ?? 4;
                if ($wa !== $wb) return $wb <=> $wa;
                return strcmp($a['code'], $b['code']);
            });

            $bins = [
                1 => ['cats' => [], 'items' => [], 'subject_ids' => []],
                2 => ['cats' => [], 'items' => [], 'subject_ids' => []],
            ];

            foreach ($items as $it) {
                // Prereq constraint within same year: if a prereq is already in sem 2, must go to sem 2.
                $forceSem = 1;
                $prereqIds = $prereqs[$it['subject_id']] ?? [];
                foreach ($prereqIds as $pid) {
                    if (in_array($pid, $bins[2]['subject_ids'], true)) { $forceSem = 2; break; }
                }

                if ($forceSem === 2) {
                    $sem = 2;
                } else {
                    $score1 = ($bins[1]['cats'][$it['cat']] ?? 0) * 1000 + count($bins[1]['items']);
                    $score2 = ($bins[2]['cats'][$it['cat']] ?? 0) * 1000 + count($bins[2]['items']);
                    $sem = ($score1 <= $score2) ? 1 : 2;
                }

                $bins[$sem]['items'][] = $it;
                $bins[$sem]['cats'][$it['cat']] = ($bins[$sem]['cats'][$it['cat']] ?? 0) + 1;
                $bins[$sem]['subject_ids'][] = $it['subject_id'];
                $placed[$it['subject_id']] = ['year' => $year, 'sem' => $sem];
            }

            $this->line("Year {$year}:");
            foreach ($bins as $sem => $bin) {
                $this->line("  Sem {$sem} (" . count($bin['items']) . "): " .
                    implode(', ', array_map(fn($i) => "{$i['code']}[{$i['cat']}]", $bin['items'])));

                if (! $this->option('dry-run')) {
                    foreach ($bin['items'] as $it) {
                        DB::table('program_subjects')
                            ->where('id', $it['id'])
                            ->update([
                                'semester_number' => $sem,
                                'updated_at'      => now(),
                            ]);
                    }
                }
            }
        }
    }

    private function categorize(string $code, string $name): string
    {
        $c = strtoupper($code);
        $n = strtolower($name);

        // Code prefix shortcuts
        if (preg_match('/^(RES|RESEARCH|THESIS)/', $c) || preg_match('/research|thesis|capstone|undergraduate study/i', $n)) return 'research';
        if (str_starts_with($c, 'FS') || preg_match('/field study|practicum|teaching internship/i', $n)) return 'field_study';
        if (str_starts_with($c, 'NSTP') || preg_match('/\bnstp\b/i', $n)) return 'nstp';
        if (str_starts_with($c, 'PE-') || str_starts_with($c, 'PE') || preg_match('/physical education|\bsports\b/i', $n)) return 'pe';
        if (str_starts_with($c, 'GEC')) return 'gec';
        if (str_starts_with($c, 'PROF') || preg_match('/pedagog|teaching and assessment|teaching profession|the teacher and|technology for teaching|assessment in learning|curriculum development/i', $n)) return 'prof_ed';
        if (preg_match('/\b(lab|laboratory)\b/i', $n)) return 'lab';
        if (preg_match('/biology|chemistry|physics|earth science|astronomy|natural science/i', $n)) return 'science';
        if (preg_match('/math|algebra|calculus|trigonom|statistic|geometry|number theory|probability/i', $n)) return 'math';
        if (preg_match('/literature|mythology|folklore|stylistic|poetry|drama|fiction|popular and emergent/i', $n)) return 'literature';
        if (preg_match('/linguistic|phonet|syntax|morpholog|language acquisition|discourse/i', $n)) return 'linguistics';
        if (str_starts_with($c, 'ENG') || str_starts_with($c, 'FIL') || preg_match('/english|filipino|wika|pagbasa|grammar|speech|journalism/i', $n)) return 'language';

        return 'other';
    }
}
