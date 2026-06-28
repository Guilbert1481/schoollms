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

// Auto-generate Statements of Account. Runs daily; the command itself reads
// each school's finance settings and only generates on that school's cadence.
Schedule::command('finance:generate-soas')->dailyAt('01:00');
