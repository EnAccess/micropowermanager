<?php

namespace App\Console\Commands;

use App\Jobs\ProspectExtract as ProspectExtractJob;

class ProspectExtract extends AbstractSharedCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:extract
                            {--company-id= : The tenant ID to run the command for (defaults to current tenant)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract installation data from database for Prospect';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int {
        try {
            $this->info('Dispatching Prospect data extraction job...');

            ProspectExtractJob::dispatch();

            $this->info('Prospect data extraction job has been dispatched successfully!');
            $this->info('Check the logs for job execution details.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error dispatching job: ' . $e->getMessage());

            return 1;
        }
    }
}
