<?php

namespace Inensus\Prospect\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Inensus\Prospect\Http\Clients\ProspectApiClient;

class PushInstallations implements ShouldQueue {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'prospect_push';

    public function __construct(private ?string $filePath = null) {}

    public function handle(ProspectApiClient $apiClient): void {
        try {
            $data = $this->loadCsvData();
            if (empty($data)) {
                Log::info('Prospect: no data to push');

                return;
            }
            $payload = ['data' => $data];
            $response = $apiClient->postInstallations($payload);

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

    private function loadCsvData(): array {
        $filePath = $this->filePath ?? $this->getLatestCsvFile();
        if (!is_file($filePath)) {
            return [];
        }
        $csvContent = file_get_contents($filePath) ?: '';
        $lines = array_values(array_filter(str_getcsv($csvContent, "\n"), fn ($l) => trim((string) $l) !== ''));
        if (empty($lines)) {
            return [];
        }
        $headers = array_map('trim', str_getcsv(array_shift($lines)));
        $data = [];
        foreach ($lines as $idx => $line) {
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
        $companyDatabase = app(\App\Models\CompanyDatabase::class)->newQuery()->first();
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
