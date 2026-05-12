<?php

namespace App\Console\Commands;

use App\Services\ProgramSubjectActivationService;
use Illuminate\Console\Command;

class SyncProgramSubjectsActivation extends Command
{
    protected $signature = 'program-subjects:sync-activation';

    protected $description = 'Activate program_subjects when enrollment opens; deactivate them after the term end date.';

    public function handle(ProgramSubjectActivationService $service): int
    {
        $result = $service->sync();

        $this->info(sprintf(
            'Program subjects sync complete: %d activated, %d deactivated.',
            $result['activated'],
            $result['deactivated']
        ));

        return Command::SUCCESS;
    }
}
