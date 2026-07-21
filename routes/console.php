<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purge chat attachments older than 24h, every hour.
Schedule::command('chat:purge-attachments')->hourly();
Schedule::command('program-subjects:sync-activation')->dailyAt('00:05');

// Flip pending deadline completions to overdue once their due datetime passes.
Schedule::command('deadlines:update-overdue')->everyFifteenMinutes();

// Auto-generate Statements of Account. Runs daily; the command itself reads
// each school's finance settings and only generates on that school's cadence.
Schedule::command('finance:generate-soas')->dailyAt('01:00');

// Email due invoices (and the SOA with every 3rd one) to students + guardians.
// Per-school opt-in via finance_settings.auto_send_invoices; idempotent
// (invoices.emailed_at), so a re-run never double-sends.
Schedule::command('finance:send-due-invoices')->dailyAt('07:00');

// Roadmap D3 — erase never-enrolled applicants' PII past the retention window.
// MANUAL-FIRST: run `php artisan pii:purge-applicants` (dry run), review, then
// `--purge` by hand. Once the counts are trusted, uncomment to run monthly.
// Schedule::command('pii:purge-applicants --purge')->monthlyOn(1, '02:00');
