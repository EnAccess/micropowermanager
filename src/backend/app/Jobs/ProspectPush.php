<?php

namespace App\Jobs;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProspectPush extends AbstractJob {
    /**
     * Create a new job instance.
     */
    public function __construct(
        private ?string $filePath = null,
    ) {
        parent::__construct();

        $this->onConnection('redis');
        $this->onQueue('prospect_push');
    }

    /**
     * Execute the job.
     */
    public function executeJob(): void {
        try {
            Log::info('Starting Prospect push job...');

            $data = $this->loadCsvData();

            if (empty($data)) {
                Log::warning('No data to push to Prospect.');

                return;
            }

            $payload = ['data' => $data];

            Log::info('Pushing '.count($data).' installation(s) to Prospect...');

            $response = $this->sendToProspect($payload);

            if ($response->successful()) {
                Log::info('Successfully pushed data to Prospect', [
                    'count' => count($data),
                    'response' => $response->json(),
                ]);
            } else {
                Log::error('Failed to push data to Prospect', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data_count' => count($data),
                ]);
                throw new \Exception('Prospect API request failed with status: '.$response->status());
            }

            Log::info('Prospect push job completed successfully!');
        } catch (\Exception $e) {
            Log::error('Error during Prospect push job: '.$e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Load data from CSV file.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadCsvData(): array {
        $filePath = $this->filePath ?? $this->getLatestCsvFile();

        if (!file_exists($filePath)) {
            throw new \Exception("CSV file not found: {$filePath}");
        }

        Log::info('Loading data from: '.basename($filePath));

        $csvContent = file_get_contents($filePath);
        $lines = str_getcsv($csvContent, "\n");

        $lines = array_filter($lines, function ($line) {
            return !empty(trim($line));
        });

        if (empty($lines)) {
            throw new \Exception('CSV file is empty or contains no valid data');
        }

        // Get headers from first line
        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);

        $data = [];

        foreach ($lines as $lineNumber => $line) {
            if (empty(trim($line))) {
                continue;
            }

            $row = str_getcsv($line);

            if (count($row) !== count($headers)) {
                Log::warning('Skipping line '.($lineNumber + 2).': column count mismatch');
                continue;
            }

            $record = array_combine($headers, $row);

            $record = array_map(function ($value) {
                $value = trim($value);

                return $value === '' ? null : $value;
            }, $record);

            // Skip records without required fields
            if (empty($record['customer_external_id']) || empty($record['serial_number'])) {
                Log::warning('Skipping record: missing customer_external_id or serial_number');
                continue;
            }

            $data[] = $record;
        }

        Log::info('Loaded '.count($data).' records from CSV');

        return $data;
    }

    /**
     * Get the latest CSV file from prospect folder.
     *
     * @return string
     */
    private function getLatestCsvFile(): string {
        // Get the company database to determine the correct prospect folder path
        $companyDatabase = app(\App\Models\CompanyDatabase::class)->newQuery()->first();
        $companyDatabaseName = $companyDatabase->getDatabaseName();

        $prospectPath = storage_path("app/prospect/{$companyDatabaseName}/");

        if (!is_dir($prospectPath)) {
            throw new \Exception("Prospect folder not found: {$prospectPath}");
        }

        $files = glob($prospectPath.'*.csv');

        if (empty($files)) {
            throw new \Exception("No CSV files found in prospect folder: {$prospectPath}");
        }

        // Find the latest file by modification time
        $latestFile = null;
        $latestTime = 0;

        foreach ($files as $file) {
            $fileTime = filemtime($file);
            if ($fileTime > $latestTime) {
                $latestTime = $fileTime;
                $latestFile = $file;
            }
        }

        if (!$latestFile) {
            throw new \Exception('No CSV file found in prospect folder');
        }

        Log::info('Auto-detected latest CSV: '.basename($latestFile));

        return $latestFile;
    }

    /**
     * Send data to Prospect API.
     *
     * @param array<string, mixed> $payload
     */
    private function sendToProspect(array $payload): Response {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.env('PROSPECT_API_TOKEN'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30)->post(env('PROSPECT_API_URL'), $payload);
    }
}
