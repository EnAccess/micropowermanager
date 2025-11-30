<?php

namespace Inensus\Prospect\Jobs;

use App\Jobs\AbstractJob;
use App\Models\DatabaseProxy;
use App\Models\Person\Person;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inensus\Prospect\Models\ProspectExtractedFile;
use Inensus\Prospect\Services\ProspectCustomerTransformer;

class ExtractCustomers extends AbstractJob {
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
            Log::info('Starting Prospect customers extraction job...');

            $data = $this->extractDataFromDatabase();

            if ($data === []) {
                Log::warning('No data to extract for Prospect customers. This may be because no customers are available.');

                return;
            }

            $fileName = $this->generateFileName();
            $filePath = $this->writeCsvFile($data, $fileName);
            $this->storeExtractedFile($fileName, $filePath, count($data));

            Log::info('Prospect customers extraction job completed successfully!', [
                'file' => basename($filePath),
                'records_count' => count($data),
            ]);
        } catch (\Exception $e) {
            Log::error('Error during Prospect customers extraction job: '.$e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract customer data from database.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractDataFromDatabase(): array {
        try {
            Log::info('Extracting customer data from database...');

            $query = Person::query()
                ->where('is_customer', 1)
                ->with([
                    'addresses.geo',
                    'addresses.city.country',
                    'citizenship',
                    'miniGrid',
                ]);

            $customers = $query->get();
            Log::info('Found '.$customers->count().' customers to process');

            $transformer = app(ProspectCustomerTransformer::class);
            $customerData = [];

            foreach ($customers as $customer) {
                try {
                    $customerData[] = $transformer->transform($customer);
                } catch (\Exception $e) {
                    Log::warning('Failed to transform customer', [
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            Log::info('Successfully processed '.count($customerData).' customers');

            return $customerData;
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

            $filePath = "prospect/{$companyId}/customers/{$fileName}";

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

            $existingFile = ProspectExtractedFile::query()
                ->whereNotNull('file_path')
                ->where('file_path', 'like', '%/customers/%')
                ->first();

            ProspectExtractedFile::updateOrCreate(
                ['id' => $existingFile?->id],
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
