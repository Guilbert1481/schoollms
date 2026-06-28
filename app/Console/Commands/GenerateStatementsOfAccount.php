<?php

namespace App\Console\Commands;

use App\Models\FinanceSetting;
use App\Models\LedgerEntry;
use App\Services\Finance\StatementService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Auto-generates Statements of Account on each school's finance-configured
 * cadence. Runs daily; for every school with auto_generate_soa enabled it
 * checks whether today is a trigger day for that school's frequency and, if so,
 * generates a SOA for the period that just closed for every student with
 * ledger activity.
 */
class GenerateStatementsOfAccount extends Command
{
    protected $signature = 'finance:generate-soas
                            {--school= : Restrict to a single school_id}
                            {--frequency= : Override the configured frequency (monthly|quarterly|per_term|annual)}
                            {--date= : Treat this date as "today" (YYYY-MM-DD), for testing/backfill}
                            {--dry-run : Report what would be generated without writing}';

    protected $description = 'Generate Statements of Account per each school\'s configured billing frequency';

    public function handle(StatementService $statements): int
    {
        $asOf  = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now();
        $dry   = (bool) $this->option('dry-run');
        $only  = $this->option('school') ? (int) $this->option('school') : null;
        $freqOverride = $this->option('frequency') ?: null;

        // Provision a finance_settings row for every school (idempotent) so
        // auto-generation works without requiring a finance manager to open the
        // settings page first. Schools with no ledger activity simply produce
        // nothing.
        $schoolIds = DB::table('schools')
            ->when($only, fn ($q) => $q->where('id', $only))
            ->pluck('id');

        $settings = $schoolIds
            ->map(fn ($id) => FinanceSetting::forSchool((int) $id))
            ->filter(fn ($s) => $s->auto_generate_soa);

        if ($settings->isEmpty()) {
            $this->info('No schools have automatic SOA generation enabled.');

            return self::SUCCESS;
        }

        $grand = 0;

        foreach ($settings as $setting) {
            $schoolId  = (int) $setting->school_id;
            $frequency = $freqOverride ?: $setting->soa_frequency;

            if ($frequency === 'on_demand') {
                continue;
            }

            $windows = $this->triggeredWindows($frequency, $asOf, (int) $setting->soa_generation_day, $schoolId);
            if (empty($windows)) {
                continue;
            }

            $studentIds = LedgerEntry::query()
                ->where('school_id', $schoolId)
                ->distinct()
                ->pluck('student_id');

            foreach ($windows as $window) {
                $count = 0;

                foreach ($studentIds as $studentId) {
                    if ($dry) {
                        $count++;
                        continue;
                    }

                    $soa = $statements->generate($schoolId, (int) $studentId, $window['start'], $window['end'], [
                        'frequency'        => $frequency,
                        'period_label'     => $window['label'],
                        'term_id'          => $window['term_id'] ?? null,
                        'academic_year_id' => $window['academic_year_id'] ?? null,
                        'due_days'         => (int) $setting->invoice_due_days,
                    ]);

                    if ($soa) {
                        $count++;
                    }
                }

                $grand += $count;
                $this->line(sprintf(
                    '[school %d] %s — %s%d statement(s) for "%s"',
                    $schoolId,
                    $frequency,
                    $dry ? 'would generate ' : '',
                    $count,
                    $window['label'],
                ));
            }

            if (! $dry) {
                $setting->update(['last_soa_run_at' => $asOf]);
            }
        }

        $this->info(($dry ? '[DRY RUN] ' : '')."Done. {$grand} statement(s) processed.");

        return self::SUCCESS;
    }

    /**
     * Windows that should be generated today for a frequency, or [] when today
     * is not a trigger day. Calendar cadences generate the period that just
     * closed; per_term generates for any term that ended today.
     *
     * @return array<int,array{start:Carbon,end:Carbon,label:string,term_id:?int,academic_year_id:?int}>
     */
    protected function triggeredWindows(string $frequency, Carbon $asOf, int $genDay, int $schoolId): array
    {
        $genDay = max(1, min(28, $genDay));

        if ($frequency === 'per_term') {
            return DB::table('terms')
                ->where('school_id', $schoolId)
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->whereDate('end_date', $asOf->toDateString())
                ->get(['id', 'name', 'start_date', 'end_date', 'academic_year_id'])
                ->map(fn ($t) => [
                    'start'            => Carbon::parse($t->start_date),
                    'end'              => Carbon::parse($t->end_date),
                    'label'            => $t->name,
                    'term_id'          => (int) $t->id,
                    'academic_year_id' => $t->academic_year_id ? (int) $t->academic_year_id : null,
                ])
                ->all();
        }

        if ($asOf->day !== $genDay) {
            return [];
        }

        $prev = $asOf->copy()->subMonthNoOverflow();

        return match ($frequency) {
            'monthly' => [[
                'start'            => $prev->copy()->startOfMonth(),
                'end'              => $prev->copy()->endOfMonth(),
                'label'            => $prev->format('F Y'),
                'term_id'          => null,
                'academic_year_id' => null,
            ]],
            'quarterly' => in_array($asOf->month, [1, 4, 7, 10], true) ? [[
                'start'            => $asOf->copy()->subQuarterNoOverflow()->firstOfQuarter(),
                'end'              => $asOf->copy()->subQuarterNoOverflow()->lastOfQuarter(),
                'label'            => 'Q'.$asOf->copy()->subQuarterNoOverflow()->quarter.' '.$asOf->copy()->subQuarterNoOverflow()->year,
                'term_id'          => null,
                'academic_year_id' => null,
            ]] : [],
            'annual' => $asOf->month === 1 ? [[
                'start'            => $asOf->copy()->subYearNoOverflow()->startOfYear(),
                'end'              => $asOf->copy()->subYearNoOverflow()->endOfYear(),
                'label'            => (string) $asOf->copy()->subYearNoOverflow()->year,
                'term_id'          => null,
                'academic_year_id' => null,
            ]] : [],
            default => [],
        };
    }
}
