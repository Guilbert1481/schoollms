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
