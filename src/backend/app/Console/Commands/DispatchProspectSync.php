<?php

namespace App\Console\Commands;

use App\Jobs\ProspectSync;

class DispatchProspectSync extends AbstractSharedCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:sync:dispatch
                            {--company-id= : The tenant ID to run the command for (defaults to current tenant)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch Prospect sync job to extract installation data and push to Prospect (new job-based implementation)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int {
        try {
            $this->info('Dispatching Prospect sync job...');

            ProspectSync::dispatch();

            $this->info('Prospect sync job dispatched successfully!');
            $this->info('The job will run asynchronously in the background.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error dispatching Prospect sync job: '.$e->getMessage());

            return 1;
        }
    }
}
