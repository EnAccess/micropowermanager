<?php

namespace App\Jobs;

use App\Services\CompanyDatabaseService;
use App\Models\Address\Address;
use App\Models\Device;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProspectExtract extends AbstractJob {
    /**
     * Create a new job instance.
     */
    public function __construct(?int $companyId = null) {
        parent::__construct($companyId);

        $this->onConnection('redis');
        $this->onQueue('prospect_extract');
    }

    /**
     * Execute the job.
     */
    public function executeJob(): void {
        try {
            Log::info('Starting Prospect data extraction...');

            $data = $this->extractDataFromDatabase();

            if ($data === []) {
                Log::warning('No data found to extract.');

                return;
            }

            $count = count($data);
            Log::info('Successfully extracted '.$count.' '.($count === 1 ? 'installation' : 'installations').' from database');

            Log::info('Generating CSV file with extracted data...');

            $fileName = $this->generateFileName();
            $filePath = $this->writeCsvFile($data, $fileName);

            Log::info('CSV file: '.$fileName);
            Log::info('File path: '.$filePath);

            Log::info('Prospect data extraction completed successfully!');
        } catch (\Exception $e) {
            Log::error('Error during extraction: '.$e->getMessage(), [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract data from database.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractDataFromDatabase(): array {
        Log::info('Loading installation data from database...');

        // Query devices with all necessary relationships
        $query = Device::query()->with([
            'device',
            'person.addresses.geo',
            'person.addresses.city.country',
            'tokens.transaction',
            'appliance',
            'assetPerson',
        ]);

        $devices = $query->get();

        $installations = [];

        foreach ($devices as $device) {
            $deviceData = $device->device;

            if (!$device->device()->exists()) {
                continue;
            }

            $deviceData->load('manufacturer');

            $person = $device->person()->first();
            $assetPerson = $device->assetPerson;

            // Create meaningful customer identifier
            $customerIdentifier = $person ? trim($person->name.' '.$person->surname) : 'Unknown Customer';

            /** @var ?Address $primaryAddress */
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

            // Determine device category
            $deviceCategory = match ($device->device_type) {
                'meter' => 'meter',
                'solar_home_system' => 'solar_home_system',
                default => 'other',
            };

            $manufacturer = $deviceData->manufacturer ?? null;

            $installation = [
                // Customer and agent identification
                'customer_external_id' => $customerIdentifier,
                'seller_agent_external_id' => $customerIdentifier,
                'installer_agent_external_id' => $customerIdentifier,
                'product_common_id' => null,
                'device_external_id' => (string) $device->id,
                'parent_external_id' => null,
                'account_external_id' => null,

                // Device specifications
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

                // Payment plan information
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

                // Dates
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

            $installations[] = $installation;
        }

        $count = count($installations);
        Log::info('Loaded '.$count.' '.($count === 1 ? 'installation' : 'installations').' from database');

        return $installations;
    }

    /**
     * Generate filename for CSV.
     */
    private function generateFileName(): string {
        $timestamp = now()->toISOString();

        return "prospect_{$timestamp}.csv";
    }

    /**
     * Write data to CSV file.
     *
     * @param array<int, array<string, mixed>> $data
     *
     */
    private function writeCsvFile(array $data, string $fileName): string {
        $headers = array_keys($data[0]);
        $csvContent = $this->arrayToCsv($data, $headers);

        // Get company database from the current context
        $companyDatabase = app(CompanyDatabaseService::class)->findByCompanyId($this->companyId);
        $companyDatabaseName = $companyDatabase->getDatabaseName();

        $filePath = "prospect/{$companyDatabaseName}/{$fileName}";

        $directory = "prospect/{$companyDatabaseName}";
        $fullDirectoryPath = storage_path("app/{$directory}");

        if (!File::isDirectory($fullDirectoryPath)) {
            File::makeDirectory($fullDirectoryPath, 0775, true);
        }

        Storage::disk('local')->put($filePath, $csvContent);

        return Storage::disk('local')->path($filePath);
    }

    /**
     * Convert array to CSV format.
     *
     * @param array<int, array<string, mixed>> $data
     * @param array<int, string>               $headers
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
}
