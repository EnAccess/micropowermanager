<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProspectPush extends AbstractSharedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:push
                            {--file= : JSON file path containing data to push}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push installation data to Prospect';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): void
    {
        $filePath = $this->option('file');

        if(!$filePath || !file_exists($filePath)) {
            $this->error("Please provide a valid --file=path/to/data.json option.");
            return;
        }

        $jsonData = json_decode(file_get_contents($filePath), true);

        if(!$jsonData || !isset($jsonData['data'])) {
            $this->error('Invalid JSON structure. Expecting {"data" [...]}');
            return;
        }

        $response = Http::withToken(env('PROSPECT_API_TOKEN'))
            ->post(env('PROSPECT_API_URL'), $jsonData);

        if($response->successful()) {
            $this->info("Data pushed to Prospect successfully.");
        } else {
            $this->error("Failed to push data to Prospect. Status: " . $response->status());
            Log::errror("Prospect push failed", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }
    }
}
