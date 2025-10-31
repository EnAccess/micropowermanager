<?php

namespace Inensus\Prospect\Jobs;

use App\Jobs\AbstractJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inensus\Prospect\Http\Clients\ProspectApiClient;
use Inensus\Prospect\Models\ProspectExtractedFile;

class PushInstallations extends AbstractJob {
    /**
     * Track the extracted file being processed.
     */
    private ?ProspectExtractedFile $extractedFile = null;

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

            if ($this->extractedFile instanceof ProspectExtractedFile) {
                $this->extractedFile->update([
                    'is_synced' => true,
                    'synced_at' => now(),
                ]);
            }
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
        if (empty($filePath) || !Storage::exists($filePath)) {
            return [];
        }
        $csvContent = Storage::get($filePath) ?: '';
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
        $extractedFile = ProspectExtractedFile::query()->first();

        if (!$extractedFile || !$extractedFile->file_path) {
            return '';
        }

        $this->extractedFile = $extractedFile;

        return $extractedFile->file_path;
    }
}
