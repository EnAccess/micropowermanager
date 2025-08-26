<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProspectSync extends AbstractSharedCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:sync
                            {--company-id= : The tenant ID to run the command for (defaults to current tenant)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract installation data from database and push to Prospect';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int {
        try {
            $this->info('Starting Prospect data extraction and push process...');

            // Step 1: Extract data from database
            $this->info('Step 1: Extracting data from database...');
            $data = $this->extractDataFromDatabase();

            if (empty($data)) {
                $this->error('No data found to extract.');

                return 1;
            }

            $count = count($data);
            $this->info('Successfully extracted '.$count.' '.($count === 1 ? 'installation' : 'installations').' from database');

            // Step 2: Generate CSV file
            $this->info('Step 2: Generating CSV file with extracted data...');
            $fileName = $this->generateFileName();
            $filePath = $this->writeCsvFile($data, $fileName);

            $this->info('CSV file: '.$fileName);
            $this->info('File path: '.$filePath);

            // Step 3: Push data to Prospect
            $this->info('Step 3: Pushing data to Prospect...');

            $this->info('Pushing '.$count.' installation(s) to Prospect...');

            $response = $this->sendToProspect(['data' => $data]);

            if ($response->successful()) {
                $this->info('Successfully pushed data to Prospect');
                $this->line('Response: '.$response->body());
                Log::info('Prospect extract and push successful', [
                    'count' => $count,
                    'response' => $response->json(),
                ]);
            } else {
                $this->error('Failed to push data to Prospect');
                $this->error('Status: '.$response->body());
                Log::error('Prospect extract and push failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data_count' => $count,
                ]);

                return 1;
            }

            $this->info('Prospect sync process completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error during extract and push process: '.$e->getMessage());
            Log::error('Prospect extract and push exception', [
                'error' => $e->getMessage(),
            ]);

            return 1;
        }
    }

    /**
     * Extract data from database.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractDataFromDatabase(): array {
        $this->info('Loading installation data from database...');

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
            $customerIdentifier = $person ? trim($person->name.' '.$person->surname) : 'Unknown Customer';

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

                'is_test' => false,
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

        $count = count($installations);
        $this->info('Loaded '.$count.' '.($count === 1 ? 'installation' : 'installations').' from database');

        return $installations;
    }

    /**
     * Generate filename for CSV.
     *
     * @return string
     */
    private function generateFileName(): string {
        $timestamp = now()->toISOString();

        return "prospect_{$timestamp}.csv";
    }

    /**
     * Write data to CSV file.
     *
     * @param array<int, array<string, mixed>> $data
     * @param string                           $fileName
     *
     * @return string
     */
    private function writeCsvFile(array $data, string $fileName): string {
        $headers = array_keys($data[0]);
        $csvContent = $this->arrayToCsv($data, $headers);

        $companyId = $this->option('company-id');
        $companyDatabase = $this->getCompanyDatabase($companyId);
        $companyDatabaseName = $companyDatabase->getDatabaseName();

        $filePath = "prospect/{$companyDatabaseName}/{$fileName}";
        Storage::disk('local')->put($filePath, $csvContent);

        return Storage::disk('local')->path($filePath);
    }

    /**
     * Convert array to CSV format.
     *
     * @param array<int, array<string, mixed>> $data
     * @param array<int, string>               $headers
     *
     * @return string
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
     * Send data to Prospect API.
     *
     * @param array<string, mixed> $payload
     *
     * @return Response
     */
    private function sendToProspect(array $payload): Response {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.env('PROSPECT_API_TOKEN'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30)->post(env('PROSPECT_API_URL'), $payload);
    }
}
