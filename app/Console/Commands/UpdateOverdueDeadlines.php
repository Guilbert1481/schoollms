<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeadlineUserCompletion;
use Carbon\Carbon;

class UpdateOverdueDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deadlines:update-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update pending deadlines to overdue if past due date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DeadlineUserCompletion::where('status', 'pending')
            ->whereHas('deadline', function ($query) {
                $query->where('due_date', '<', Carbon::now());
            })
            ->update(['status' => 'overdue']);

        $this->info('Overdue deadlines updated successfully.');

        return Command::SUCCESS;
    }
}