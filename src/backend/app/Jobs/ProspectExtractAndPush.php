<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\CompanyDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProspectExtractAndPush extends AbstractJob
{
    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        parent::__construct('ProspectExtractAndPush');
    }

    /**
     * Execute the job.
     */
    public function executeJob(): void
    {
        try {
            Log::info('Starting Prospect extract and push job');

            // Step 1: Extract data from database
            $data = $this->extractDataFromDatabase();

            if (empty($data)) {
                Log::info('No data found to extract and push to Prospect');
                return;
            }

            $count = count($data);
            Log::info("Successfully extracted {$count} installations from database");

            // Step 2: Generate CSV file
            $fileName = $this->generateFileName();
            $filePath = $this->writeCsvFile($data, $fileName);

            Log::info("Generated CSV file: {$fileName}");

            // Step 3: Push data to Prospect
            $payload = ['data' => $data];

            Log::info("Pushing {$count} installation(s) to Prospect...");

            $response = $this->sendToProspect($payload);

            if ($response->successful()) {
                Log::info('Successfully pushed data to Prospect', [
                    'count' => $count,
                    'response' => $response->json(),
                ]);

                // Clean up the CSV file after successful push
                $this->cleanupCsvFile($filePath);
            } else {
                Log::error('Failed to push data to Prospect', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data_count' => $count,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error during Prospect extract and push job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract data from database
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractDataFromDatabase(): array
    {
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

            if (!$deviceData instanceof Model) {
                continue;
            }

            $deviceData->load('manufacturer');

            $person = $device->person;
            $assetPerson = $device->assetPerson;

            // Create meaningful customer identifier
            $customerIdentifier = $person ? trim($person->name . ' ' . $person->surname) : 'Unknown Customer';

            $primaryAddress = null;
            if ($person) {
                $primaryAddress = $person->addresses->where('is_primary', 1)->first() ?? $person->addresses->first();
            }

            $latitude = null;
            $longitude = null;

            if ($primaryAddress && $primaryAddress->geo && $primaryAddress->geo->points) {
                $coordinates = explode(',', $primaryAddress->geo->points);
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
                'dc_input_source' => $deviceCategory === 'solar_home_system' ? 'solar' : null,
                'firmware_version' => null,
                'manufacturer' => $deviceData->manufacturer->name ?? 'Unknown',
                'model' => null,
                'primary_use' => null,
                'rated_power_w' => null,
                'pv_power_w' => null,
                'serial_number' => $deviceData->serial_number ?? '',
                'site_name' => $primaryAddress?->street ?? null,

                // Payment plan information
                'payment_plan_amount_financed_principal' => null,
                'payment_plan_amount_financed_interest' => null,
                'payment_plan_amount_financed_total' => null,
                'payment_plan_amount_down_payment' => $assetPerson?->down_payment ?? null,
                'payment_plan_cash_price' => $assetPerson?->total_cost ?? null,
                'payment_plan_currency' => null,
                'payment_plan_installment_amount' => null,
                'payment_plan_number_of_installments' => $assetPerson?->rate_count ?? null,
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

                'is_test' => false, // Always false for automated jobs
                'latitude' => $latitude,
                'longitude' => $longitude,
                'country' => $primaryAddress?->city?->country?->country_code ?? null,
                'location_area_1' => $primaryAddress?->city?->country?->country_name ?? null,
                'location_area_2' => $primaryAddress?->city?->name ?? null,
                'location_area_3' => null,
                'location_area_4' => null,
                'location_area_5' => null,
            ];

            $installations[] = $installation;
        }

        return $installations;
    }

    /**
     * Generate CSV filename
     */
    private function generateFileName(): string
    {
        $timestamp = now()->toISOString();
        return "prospect_{$timestamp}.csv";
    }

    /**
     * Write data to CSV file
     *
     * @param array<int, array<string, mixed>> $data
     */
    private function writeCsvFile(array $data, string $fileName): string
    {
        $headers = array_keys($data[0]);
        $csvContent = $this->arrayToCsv($data, $headers);

        $companyDatabase = $this->getCompanyDatabase();
        $companyDatabaseName = $companyDatabase->getDatabaseName();

        $filePath = "prospect/{$companyDatabaseName}/{$fileName}";
        Storage::disk('local')->put($filePath, $csvContent);

        return Storage::disk('local')->path($filePath);
    }

    /**
     * Convert array to CSV format
     *
     * @param array<int, array<string, mixed>> $data
     * @param array<int, string> $headers
     */
    private function arrayToCsv(array $data, array $headers): string
    {
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
     * Get the company database
     */
    private function getCompanyDatabase(): CompanyDatabase
    {
        try {
            $companyDatabase = app(CompanyDatabase::class)->newQuery()->first();
            if (!$companyDatabase) {
                throw new \Exception('No company database found');
            }
            return $companyDatabase;
        } catch (\Exception $e) {
            throw new \Exception('Unable to find company database: ' . $e->getMessage());
        }
    }

    /**
     * Send data to Prospect API
     *
     * @param array<string, mixed> $payload
     */
    private function sendToProspect(array $payload): \Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PROSPECT_API_TOKEN'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30)->post(env('PROSPECT_API_URL'), $payload);
    }

    /**
     * Clean up CSV file after successful push
     */
    private function cleanupCsvFile(string $filePath): void
    {
        try {
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info('Cleaned up CSV file after successful push');
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup CSV file', ['error' => $e->getMessage()]);
        }
    }
}
