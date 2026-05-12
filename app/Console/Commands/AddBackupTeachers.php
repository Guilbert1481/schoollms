<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddBackupTeachers extends Command
{
    protected $signature = 'teachers:add-backups {--leave=1 : Number of subjects to leave with only one teacher}';
    protected $description = 'Ensure each active subject has at least 2 qualified teachers, leaving N subjects single-coverage';

    public function handle(): int
    {
        $active = DB::table('program_subjects')->where('is_active', true)->distinct()->pluck('subject_id');
        $leave  = (int) $this->option('leave');

        $teachers = DB::table('teachers')
            ->leftJoin('profiles', 'profiles.id', '=', 'teachers.profile_id')
            ->select('teachers.id', 'teachers.specialization', 'profiles.first_name', 'profiles.last_name')
            ->get();

        $subjects = DB::table('subjects')->whereIn('id', $active)->get()->keyBy('id');

        $prefixGroup = function (string $code): string {
            $code = strtoupper($code);
            if (str_starts_with($code, 'PROF') || str_starts_with($code, 'PED')) return 'PROF';
            if (str_starts_with($code, 'ENG')) return 'ENG';
            if (str_starts_with($code, 'MATH')) return 'MATH';
            if (str_starts_with($code, 'GE') || str_starts_with($code, 'NSTP') || str_starts_with($code, 'PE') || str_starts_with($code, 'FIL')) return 'GE';
            if (str_starts_with($code, 'FS')) return 'PROF';
            return 'GE';
        };

        $teacherGroup = function (?string $spec): string {
            $spec = strtolower((string) $spec);
            if (str_contains($spec, 'english') || str_contains($spec, 'literature')) return 'ENG';
            if (str_contains($spec, 'math') || str_contains($spec, 'physics') || str_contains($spec, 'chem') || str_contains($spec, 'bio') || str_contains($spec, 'science')) return 'MATH';
            if (str_contains($spec, 'history') || str_contains($spec, 'social') || str_contains($spec, 'values') || str_contains($spec, 'research')) return 'PROF';
            return 'GE';
        };

        // Order subjects so the LAST `leave` ones are skipped. Keep deterministic order.
        $ordered = $active->sort()->values();
        $skip = $ordered->slice($ordered->count() - $leave)->all();

        $added = 0;
        foreach ($ordered as $sid) {
            if (in_array($sid, $skip, true)) continue;
            $existing = DB::table('teacher_subjects')->where('subject_id', $sid)->pluck('teacher_id')->all();
            if (count($existing) >= 2) continue;

            $subj  = $subjects[$sid] ?? null;
            $group = $subj ? $prefixGroup((string) ($subj->code ?? '')) : 'GE';

            // candidates: matching group, not already assigned, sorted by current load asc
            $loads = DB::table('teacher_subjects')->select('teacher_id', DB::raw('COUNT(*) as c'))->groupBy('teacher_id')->pluck('c', 'teacher_id')->all();

            $candidates = $teachers->filter(fn($t) => $teacherGroup($t->specialization) === $group && ! in_array($t->id, $existing, true))->values();
            if ($candidates->isEmpty()) {
                // fallback: any teacher not already assigned
                $candidates = $teachers->filter(fn($t) => ! in_array($t->id, $existing, true))->values();
            }
            if ($candidates->isEmpty()) continue;

            $candidates = $candidates->sortBy(fn($t) => $loads[$t->id] ?? 0)->values();
            $pick = $candidates->first();

            DB::table('teacher_subjects')->insert([
                'teacher_id' => $pick->id,
                'subject_id' => $sid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $added++;
            $this->line(sprintf('+ subject %d (%s) -> teacher %d %s %s [%s]',
                $sid, $subj->code ?? '?', $pick->id, $pick->first_name, $pick->last_name, $pick->specialization));
        }

        $this->info("Added {$added} backup teacher_subjects rows. Skipped " . count($skip) . " subject(s) by request.");
        return self::SUCCESS;
    }
}
