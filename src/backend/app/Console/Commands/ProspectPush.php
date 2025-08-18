<?php

namespace App\Console\Commands;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProspectPush extends AbstractSharedCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:push
                            {--file= : CSV file path containing data to push}
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
            $data = $this->loadCsvData();

            if (empty($data)) {
                $this->error('No data to push to Prospect.');
                return;
            }

            $payload = ['data' => $data];

            if ($this->option('dry-run')) {
                $this->info('DRY RUN - would send the following data:');
                $this->line(json_encode($payload, JSON_PRETTY_PRINT));
                return;
            }

            $this->info('Pushing '.count($data).' installation(s) to Prospect...');

            $response = $this->sendToProspect($payload);

            if ($response->successful()) {
                $this->info('Successfully pushed data to Prospect');
                $this->line('Response: '.$response->body());
                Log::info('Prospect push successful', [
                    'count' => count($data),
                    'response' => $response->json(),
                ]);
            } else {
                $this->error('Failed to push data to Prospect');
                $this->error('Status: '.$response->body());
                Log::error('Prospect push failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data_count' => count($data),
                ]);
            }
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
            Log::error('Prospect push exception', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Load data from CSV file
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadCsvData(): array {
        $filePath = $this->option('file') ?? $this->getLatestCsvFile();

        if (!file_exists($filePath)) {
            throw new \Exception("CSV file not found: {$filePath}");
        }

        $this->info("Loading data from: " . basename($filePath));

        $csvContent = file_get_contents($filePath);
        $lines = str_getcsv($csvContent, "\n");

        if (empty($lines)) {
            throw new \Exception('CSV file is empty');
        }

        // Get headers from first line
        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);

        $data = [];
        $isTest = $this->option('test');

        foreach ($lines as $lineNumber => $line) {
            if (empty(trim($line))) continue; // Skip empty lines

            $row = str_getcsv($line);

            if (count($row) !== count($headers)) {
                $this->warn("Skipping line " . ($lineNumber + 2) . ": column count mismatch");
                continue;
            }

            $record = array_combine($headers, $row);

            $record = array_map(function($value) {
                $value = trim($value);
                return $value === '' ? null : $value;
            }, $record);

            if ($isTest) {
                $record['is_test'] = true;
            }

            // Skip records without required fields
            if (empty($record['customer_external_id']) || empty($record['serial_number'])) {
                $this->warn("Skipping record: missing customer_external_id or serial_number");
                continue;
            }

            $data[] = $record;
        }

        $this->info('Loaded ' . count($data) . ' records from CSV');

        return $data;
    }

    /**
     * Get the latest CSV file from common directories
     *
     * @return string
     */
    private function getLatestCsvFile(): string {
        $searchPaths = [
            storage_path('app/prospects/'),
            storage_path('app/'),
            base_path('prospects/'),
            base_path(),
        ];

        $latestFile = null;
        $latestTime = 0;

        foreach ($searchPaths as $path) {
            if (!is_dir($path)) continue;

            $files = glob($path . '*.csv');
            foreach ($files as $file) {
                $fileTime = filemtime($file);
                if ($fileTime > $latestTime) {
                    $latestTime = $fileTime;
                    $latestFile = $file;
                }
            }
        }

        if (!$latestFile) {
            throw new \Exception('No CSV file specified and no CSV files found');
        }

        $this->info("Auto-detected latest CSV: " . basename($latestFile));
        return $latestFile;
    }

    /**
     * Send data to Prospect API
     *
     * @param array<string, mixed> $payload
     */
    private function sendToProspect(array $payload): Response {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PROSPECT_API_TOKEN'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30)->post(env('PROSPECT_API_URL'), $payload);
    }
}
