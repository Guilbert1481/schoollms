<?php

namespace App\Console\Commands;

use App\Models\FinanceSetting;
use App\Services\Finance\FinanceMailService;
use Illuminate\Console\Command;

/**
 * Daily auto-send: emails every invoice whose billing date has arrived (and
 * was never emailed) to the student + guardians, per school opt-in. Every 3rd
 * emailed invoice per enrollment also carries the updated SOA.
 */
class SendDueInvoices extends Command
{
    protected $signature = 'finance:send-due-invoices {--school= : Only process this school id}';

    protected $description = 'Email due, unsent invoices (with the SOA on every 3rd send) to students and guardians';

    public function handle(FinanceMailService $mailer): int
    {
        $schoolIds = FinanceSetting::query()
            ->where('auto_send_invoices', true)
            ->when($this->option('school'), fn ($q, $id) => $q->where('school_id', (int) $id))
            ->pluck('school_id');

        if ($schoolIds->isEmpty()) {
            $this->info('No schools have invoice auto-send enabled.');

            return self::SUCCESS;
        }

        $total = 0;
        foreach ($schoolIds as $schoolId) {
            $sent = $mailer->sendDueInvoicesForSchool((int) $schoolId);
            $total += $sent;
            $this->info("School {$schoolId}: {$sent} invoice(s) emailed.");
        }

        $this->info("Done — {$total} invoice(s) emailed.");

        return self::SUCCESS;
    }
}
