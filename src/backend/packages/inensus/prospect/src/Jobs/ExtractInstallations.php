<?php

namespace Inensus\Prospect\Jobs;

use App\Jobs\AbstractJob;
use App\Models\DatabaseProxy;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inensus\Prospect\Models\ProspectExtractedFile;
use Inensus\Prospect\Services\ProspectInstallationTransformer;

class ExtractInstallations extends AbstractJob {
    /**
     * Create a new job instance.
     */
    public function __construct(?int $companyId = null) {
        parent::__construct($companyId);

        $this->onConnection('redis');
        $this->onQueue('prospect');
    }

    /**
     * Execute the job.
     */
    public function executeJob(): void {
        try {
            Log::info('Starting Prospect installations extraction job...');

            $data = $this->extractDataFromDatabase();

            if ($data === []) {
                Log::warning('No data to extract for Prospect installations. This may be because no devices are available.');

                return;
            }

            $fileName = $this->generateFileName();
            $filePath = $this->writeCsvFile($data, $fileName);
            $this->storeExtractedFile($fileName, $filePath, count($data));

            Log::info('Prospect installations extraction job completed successfully!', [
                'file' => basename($filePath),
                'records_count' => count($data),
            ]);
        } catch (\Exception $e) {
            Log::error('Error during Prospect installations extraction job: '.$e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract installation data from database.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractDataFromDatabase(): array {
        try {
            Log::info('Extracting device data from database...');

            $query = Device::query()->with([
                'device',
                'person.addresses.geo',
                'person.addresses.city.country',
                'tokens.transaction',
                'appliance',
                'appliancePerson',
            ]);

            $devices = $query->get();
            Log::info('Found '.$devices->count().' devices to process');

            $transformer = app(ProspectInstallationTransformer::class);
            $installations = [];

            foreach ($devices as $device) {
                if (!$device->device()->exists()) {
                    continue;
                }

                try {
                    $installations[] = $transformer->transform($device);
                } catch (\Exception $e) {
                    Log::warning('Failed to transform device', [
                        'device_id' => $device->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            Log::info('Successfully processed '.count($installations).' installations');

            return $installations;
        } catch (\Exception $e) {
            Log::error('Error extracting data from database: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a unique filename for the CSV file.
     */
    private function generateFileName(): string {
        $timestamp = now()->toISOString();

        return "prospect_{$timestamp}.csv";
    }

    /**
     * Write CSV data to file.
     *
     * @param array<int, array<string, mixed>> $data
     */
    private function writeCsvFile(array $data, string $fileName): string {
        try {
            Log::info('Writing CSV file: '.$fileName);

            $headers = array_keys($data[0]);
            $csvContent = $this->arrayToCsv($data, $headers);

            $user = User::query()->first();
            $databaseProxy = app(DatabaseProxy::class);
            $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();

            $filePath = "prospect/{$companyId}/{$fileName}";

            Storage::put($filePath, $csvContent);

            Log::info('CSV file written successfully', [
                'file' => basename($filePath),
                'size' => strlen($csvContent),
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Error writing CSV file: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Convert array data to CSV format.
     *
     * @param array<int, array<string, mixed>> $data
     * @param array<string>                    $headers
     */
    private function arrayToCsv(array $data, array $headers): string {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $csvRow[] = $row[$header] ?? '';
            }
            fputcsv($output, $csvRow);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Store extracted file information in database.
     */
    private function storeExtractedFile(string $fileName, string $filePath, int $recordsCount): void {
        try {
            $fileSize = Storage::exists($filePath) ? Storage::size($filePath) : null;

            ProspectExtractedFile::updateOrCreate(
                ['id' => 1],
                [
                    'filename' => $fileName,
                    'file_path' => $filePath,
                    'records_count' => $recordsCount,
                    'file_size' => $fileSize,
                    'extracted_at' => now(),
                    'is_synced' => false,
                ]
            );

            Log::info('Updated extracted file information in database', [
                'filename' => $fileName,
                'records_count' => $recordsCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing extracted file information: '.$e->getMessage());
        }
    }
}
