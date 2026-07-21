<?php

namespace App\Console\Commands;

use App\Services\Privacy\ApplicantPurger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Roadmap D3 — erase the PII of never-enrolled applicants past the retention
 * window (config/privacy.php). Dry-run by default: it reports exactly who and
 * what would be deleted. Pass --purge to actually erase. Destructive and
 * irreversible (recovery is via backup only), so it ships behind the dry-run
 * gate and is scheduled manual-first — run the dry-run, eyeball it, then --purge.
 */
class PurgeApplicantPii extends Command
{
    protected $signature = 'pii:purge-applicants
        {--purge : Actually delete (default is a dry run)}
        {--limit=0 : Cap how many applicants are processed this run (0 = no cap)}';

    protected $description = 'Erase PII of never-enrolled applicants past the retention window (Roadmap D3 / RA 10173)';

    public function handle(ApplicantPurger $purger): int
    {
        $commit = (bool) $this->option('purge');
        $limit = (int) $this->option('limit');

        if (! $commit) {
            $this->warn('DRY RUN — nothing will be deleted. Re-run with --purge to erase.');
        }

        $this->line(sprintf(
            'Policy: decided applications kept %d month(s), abandoned drafts %d day(s); mode=%s.',
            (int) config('privacy.applicant_retention_months'),
            (int) config('privacy.abandoned_draft_days'),
            config('privacy.purge_depth') === 'scrub' ? 'scrub' : 'hard',
        ));

        $due = $purger->dueForPurge();

        if ($limit > 0) {
            $due = $due->take($limit);
        }

        if ($due->isEmpty()) {
            $this->info('No applicants are past their retention window. Nothing to do.');

            return self::SUCCESS;
        }

        $rows = [];
        $totals = ['files' => 0, 'users' => 0];

        foreach ($due as $student) {
            try {
                $s = $purger->purge($student, $commit);
            } catch (\Throwable $e) {
                // One bad record must not abort the batch.
                $this->error("Skipped student #{$student->id}: {$e->getMessage()}");
                Log::error('pii.purge.failed', ['student_id' => $student->id, 'error' => $e->getMessage()]);

                continue;
            }

            $totals['files'] += $s['files_unlinked'];
            $totals['users'] += $s['user_deleted'] ? 1 : 0;

            $rows[] = [
                $s['student_id'],
                $s['school_id'],
                $s['reason'],
                $s['last_activity'],
                $s['enrollments'],
                $s['documents'],
                $s['drafts'],
                $s['guardians'],
                $s['files_unlinked'],
                $s['user_deleted'] ? 'yes' : '—',
            ];
        }

        $this->table(
            ['Student', 'School', 'Reason', 'Last activity', 'Enroll', 'Docs', 'Drafts', 'Guard.', 'Files', 'User del'],
            $rows,
        );

        $verb = $commit ? 'Purged' : 'Would purge';
        $this->info(sprintf(
            '%s %d applicant(s); %d file(s), %d login(s).',
            $verb, count($rows), $totals['files'], $totals['users'],
        ));

        if (! $commit) {
            $this->warn('Re-run with --purge to apply. Take a fresh backup first — this cannot be undone.');
        }

        return self::SUCCESS;
    }
}
