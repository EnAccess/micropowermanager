<?php

namespace Inensus\Prospect\Jobs;

use App\Jobs\AbstractJob;
use App\Models\CompanyDatabase;
use App\Models\Device;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inensus\Prospect\Models\ProspectExtractedFile;

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
                'assetPerson',
            ]);

            $devices = $query->get();
            Log::info('Found '.$devices->count().' devices to process');

            $installations = [];

            foreach ($devices as $device) {
                if (!$device->device()->exists()) {
                    continue;
                }
                $deviceData = $device->device;
                $deviceData->load('manufacturer');

                $person = $device->person()->first();
                $assetPerson = $device->assetPerson;
                $customerIdentifier = $person ? trim(($person->name ?? '').' '.($person->surname ?? '')) : 'Unknown Customer';

                $primaryAddress = null;
                if ($person && $person->addresses()->exists()) {
                    $primaryAddress = $person->addresses->where('is_primary', 1)->first() ?: $person->addresses->first();
                }

                $latitude = null;
                $longitude = null;

                $geoInfo = $primaryAddress ? $primaryAddress->geo : null;
                if ($geoInfo && $geoInfo->points) {
                    $coordinates = explode(',', $geoInfo->points);
                    if (count($coordinates) >= 2) {
                        $latitude = (float) trim($coordinates[0]);
                        $longitude = (float) trim($coordinates[1]);
                    }
                }

                $deviceCategory = match ($device->device_type) {
                    'meter' => 'meter',
                    'solar_home_system' => 'solar_home_system',
                    default => 'other',
                };

                $manufacturer = $deviceData->manufacturer ?? null;

                $installations[] = [
                    'customer_external_id' => $customerIdentifier,
                    'seller_agent_external_id' => $customerIdentifier,
                    'installer_agent_external_id' => $customerIdentifier,
                    'product_common_id' => null,
                    'device_external_id' => (string) $device->id,
                    'parent_external_id' => null,
                    'account_external_id' => null,
                    'battery_capacity_wh' => null,
                    'usage_category' => 'household',
                    'usage_sub_category' => null,
                    'device_category' => $deviceCategory,
                    'ac_input_source' => null,
                    'dc_input_source' => ($deviceCategory === 'solar_home_system') ? 'solar' : null,
                    'firmware_version' => null,
                    'manufacturer' => $manufacturer ? $manufacturer->name : 'Unknown',
                    'model' => null,
                    'primary_use' => null,
                    'rated_power_w' => null,
                    'pv_power_w' => null,
                    'serial_number' => $deviceData->serial_number ?? '',
                    'site_name' => $primaryAddress->street ?? null,
                    'payment_plan_amount_financed_principal' => null,
                    'payment_plan_amount_financed_interest' => null,
                    'payment_plan_amount_financed_total' => null,
                    'payment_plan_amount_down_payment' => $assetPerson->down_payment ?? null,
                    'payment_plan_cash_price' => $assetPerson->total_cost ?? null,
                    'payment_plan_currency' => null,
                    'payment_plan_installment_amount' => null,
                    'payment_plan_number_of_installments' => $assetPerson->rate_count ?? null,
                    'payment_plan_installment_period_days' => null,
                    'payment_plan_days_financed' => null,
                    'payment_plan_days_down_payment' => null,
                    'payment_plan_category' => 'paygo',
                    'purchase_date' => $device->created_at->format('Y-m-d'),
                    'installation_date' => $device->created_at->format('Y-m-d'),
                    'repossession_date' => null,
                    'paid_off_date' => null,
                    'repossession_category' => null,
                    'write_off_date' => null,
                    'write_off_reason' => null,
                    'is_test' => false,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'country' => $primaryAddress->city->country->country_code ?? null,
                    'location_area_1' => $primaryAddress->city->country->country_name ?? null,
                    'location_area_2' => $primaryAddress->city->name ?? null,
                    'location_area_3' => null,
                    'location_area_4' => null,
                    'location_area_5' => null,
                ];
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

            $companyDatabase = app(CompanyDatabase::class)->newQuery()->first();
            $companyDatabaseName = $companyDatabase->getDatabaseName();

            $filePath = "prospect/{$companyDatabaseName}/{$fileName}";
            $directory = "prospect/{$companyDatabaseName}";
            $fullDirectoryPath = storage_path("app/{$directory}");

            if (!File::isDirectory($fullDirectoryPath)) {
                Log::info('Creating directory: '.$fullDirectoryPath);
                File::makeDirectory($fullDirectoryPath, 0775, true);
            }

            Storage::disk('local')->put($filePath, $csvContent);

            $fullPath = Storage::disk('local')->path($filePath);
            Log::info('CSV file written successfully', [
                'file' => basename($fullPath),
                'size' => filesize($fullPath),
            ]);

            return $fullPath;
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
            $companyDatabase = app(CompanyDatabase::class)->newQuery()->first();
            $companyDatabaseName = $companyDatabase->getDatabaseName();

            $relativePath = "prospect/{$companyDatabaseName}/{$fileName}";
            $fileSize = file_exists($filePath) ? filesize($filePath) : null;

            ProspectExtractedFile::updateOrCreate(
                ['id' => 1],
                [
                    'filename' => $fileName,
                    'file_path' => $relativePath,
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
