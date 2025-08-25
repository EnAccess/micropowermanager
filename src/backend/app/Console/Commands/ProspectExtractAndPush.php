<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProspectExtractAndPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:extract-and-push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract installation data and push to Prospect API using existing commands';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $this->info('Starting Prospect extract and push process...');

            // Step 1: Extract data using existing command
            $this->info('Step 1: Extracting data from database...');
            $extractResult = Artisan::call('prospect:extract');

            if ($extractResult !== 0) {
                $this->error('Data extraction failed with exit code: ' . $extractResult);
                return $extractResult;
            }

            $this->info('Data extraction completed successfully');

            // Step 2: Push data using existing command
            $this->info('Step 2: Pushing data to Prospect...');
            $pushResult = Artisan::call('prospect:push');

            if ($pushResult !== 0) {
                $this->error('Data push failed with exit code: ' . $pushResult);
                return $pushResult;
            }

            $this->info('Data push completed successfully');
            $this->info('Prospect extract and push process completed successfully!');

            return 0;

        } catch (\Exception $e) {
            $this->error('Error during Prospect extract and push: ' . $e->getMessage());
            return 1;
        }
    }
}
