<?php

namespace App\Console\Commands;

use App\Jobs\ProspectPushJob;
use Illuminate\Support\Facades\Log;

class ProspectPush extends AbstractSharedCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:push
                            {--file= : CSV file path containing data to push}
                            {--company-id= : The tenant ID to run the command for (defaults to current tenant)}
                            {--test : Mark data as test data}
                            {--dry-run : Show what would be sent without actually sending}';

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
     * @return void
     */
    public function handle(): void {
        try {
            $this->info('Dispatching ProspectPushJob...');

            ProspectPushJob::dispatch(
                $this->option('file'),
                $this->option('test'),
                $this->option('dry-run')
            );

            $this->info('ProspectPushJob dispatched successfully. Check the queue for progress.');

        } catch (\Exception $e) {
            $this->error('Error dispatching job: ' . $e->getMessage());
            Log::error('Prospect push job dispatch failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
