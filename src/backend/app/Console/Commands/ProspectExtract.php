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
                            {--company-id= : The company ID to run the command for (defaults to all companies if not specified)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract installation data from database for Prospect';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int {
        try {
            $companyId = $this->option('company-id');

            if ($companyId) {
                $this->info("Dispatching Prospect data extraction job for company ID: {$companyId}");
                ProspectExtractJob::dispatch((int) $companyId);
                $this->info('Prospect data extraction job has been dispatched successfully!');
            } else {
                $this->info('Dispatching Prospect data extraction job for all companies...');
                ProspectExtractJob::dispatchForAllTenants();
                $this->info('Prospect data extraction jobs have been dispatched for all companies!');
            }

            $this->info('Check the logs for job execution details.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error dispatching job: ' . $e->getMessage());

            return 1;
        }
    }
}
