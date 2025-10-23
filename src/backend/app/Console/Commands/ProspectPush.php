<?php

namespace App\Console\Commands;

use App\Jobs\ProspectPush as ProspectPushJob;

class ProspectPush extends AbstractSharedCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:push
                            {--file= : CSV file path containing data to push}
                            {--company-id= : The company ID to run the command for (defaults to all companies if not specified)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push installation data to Prospect from CSV file';

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
     */
    public function handle(): int {
        try {
            $companyId = $this->option('company-id');
            $filePath = $this->option('file');

            if ($companyId) {
                $this->info("Dispatching Prospect push job for company ID: {$companyId}");
                dispatch(new ProspectPushJob((int) $companyId, $filePath));
                $this->info('Prospect push job has been dispatched successfully!');
            } else {
                $this->info('Dispatching Prospect push job for all companies...');
                ProspectPushJob::dispatchForAllTenants($filePath);
                $this->info('Prospect push jobs have been dispatched for all companies!');
            }

            $this->info('Check the logs for job execution details.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error dispatching job: '.$e->getMessage());

            return 1;
        }
    }
}
