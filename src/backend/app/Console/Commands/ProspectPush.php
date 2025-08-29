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
                            {--company-id= : The tenant ID to run the command for (defaults to current tenant)}';

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
     *
     * @return int
     */
    public function handle(): int {
        try {
            $this->info('Dispatching Prospect push job...');

            // Get command options
            $filePath = $this->option('file');

            // Dispatch the job with options
            ProspectPushJob::dispatch($filePath);

            $this->info('Prospect push job has been dispatched successfully!');
            $this->info('Check the logs for job execution details.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error dispatching job: ' . $e->getMessage());

            return 1;
        }
    }
}
