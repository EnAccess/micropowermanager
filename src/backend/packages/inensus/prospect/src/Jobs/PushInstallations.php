<?php

namespace Inensus\Prospect\Jobs;

use App\Jobs\AbstractJob;
use App\Models\CompanyDatabase;
use Illuminate\Support\Facades\Log;
use Inensus\Prospect\Http\Clients\ProspectApiClient;

class PushInstallations extends AbstractJob {
    /**
     * Create a new job instance.
     */
    public function __construct(
        ?int $companyId = null,
        private ?string $filePath = null,
    ) {
        parent::__construct($companyId);

        $this->onConnection('redis');
        $this->onQueue('prospect');
    }

    /**
     * Execute the job.
     */
    public function executeJob(): void {
        try {
            $data = $this->loadCsvData();
            if ($data === []) {
                Log::info('Prospect: no data to push');

                return;
            }
            $payload = ['data' => $data];
            $response = app(ProspectApiClient::class)->postInstallations($payload);

            if ($response->failed()) {
                Log::error('Prospect: push failed', ['status' => $response->status(), 'body' => $response->body()]);
                throw new \RuntimeException('Prospect push failed');
            }
            Log::info('Prospect: push success', ['count' => count($data)]);
        } catch (\Exception $e) {
            Log::error('Prospect: push error '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Load CSV data and return as array of records.
     *
     * @return array<int, array<string, string|null>>
     */
    private function loadCsvData(): array {
        $filePath = $this->filePath ?? $this->getLatestCsvFile();
        if (!is_file($filePath)) {
            return [];
        }
        $csvContent = file_get_contents($filePath) ?: '';
        $lines = array_values(array_filter(str_getcsv($csvContent, "\n"), fn ($l): bool => trim((string) $l) !== ''));
        if ($lines === []) {
            return [];
        }
        $headers = array_map(trim(...), str_getcsv(array_shift($lines)));
        $data = [];
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) !== count($headers)) {
                continue;
            }
            $record = array_combine($headers, $row);
            $record = array_map(function ($v) {
                $v = trim((string) $v);

                return $v === '' ? null : $v;
            }, $record);
            if (empty($record['customer_external_id']) || empty($record['serial_number'])) {
                continue;
            }
            $data[] = $record;
        }

        return $data;
    }

    private function getLatestCsvFile(): string {
        $companyDatabase = app(CompanyDatabase::class)->newQuery()->first();
        $companyDatabaseName = $companyDatabase->getDatabaseName();
        $prospectPath = storage_path("app/prospect/{$companyDatabaseName}/");
        $files = glob($prospectPath.'*.csv') ?: [];
        $latestFile = '';
        $latestTime = 0;
        foreach ($files as $file) {
            $t = @filemtime($file) ?: 0;
            if ($t > $latestTime) {
                $latestTime = $t;
                $latestFile = $file;
            }
        }

        return $latestFile;
    }
}
