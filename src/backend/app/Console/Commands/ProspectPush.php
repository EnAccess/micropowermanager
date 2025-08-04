<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProspectPush extends AbstractSharedCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:push
                            {--file= : JSON file path containing data to push}
                            {--database : Load data from database}
                            {--limit= : Limit number of records when loading from database}
                            {--test : Mark data as test data}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push installation data to Prospect';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        try {
            $data = $this->prepareData();

            if (empty($data)) {
                $this->error('No data to push to Prospect.');

                return;
            }

            $payload = ['data' => $data];

            if ($this->option('dry-run')) {
                $this->info('DRY RUN - would send the following data:');
                $this->line(json_encode($payload, JSON_PRETTY_PRINT));

                return;
            }

            $this->info('Pushing '.count($data).' installation(s) to Prospect...');

            $response = $this->sendToProspect($payload);

            if ($response->successful()) {
                $this->info('Successfully pushed data to Prospect');
                $this->line('Response: '.$response->body());
                Log::info('Prospect push successful', [
                    'count' => count($data),
                    'response' => $response->json(),
                ]);
            } else {
                $this->error('Failed to push data to Prospect');
                $this->error('Status: '.$response->body());
                Log::error('Prospect push failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data_count' => count($data),
                ]);
            }
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
            Log::error('Prospect push exception', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function prepareData(): array {
        if ($this->option('file')) {
            return $this->loadDataFromFile();
        }

        if ($this->option('database')) {
            return $this->loadDataFromDatabase();
        }

        return $this->getDefaultTestData();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadDataFromFile(): array {
        $filePath = $this->option('file');

        if (!$filePath || !file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $jsonData = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON in file: '.json_last_error_msg());
        }

        return isset($jsonData['data']) ? $jsonData['data'] : [$jsonData];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadDataFromDatabase(): array {
        $this->info('Loading installation data from database...');

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $isTest = (bool) $this->option('test');

        // Query devices with all necessary relationships
        $query = Device::query()->with([
            'device',
            'person.addresses.geo',
            'person.addresses.city.country',
            'tokens.transaction',
            'appliance',
            'assetPerson',
        ]);

        if ($limit) {
            $query->limit($limit);
        }

        $devices = $query->get();

        $installations = [];

        foreach ($devices as $device) {
            $deviceData = $device->device;

            if (!$deviceData instanceof Model) {
                continue;
            }

            // Load manufacturer relationship dynamically
            $deviceData->load('manufacturer');

            $person = $device->person;
            $primaryAddress = $person->addresses->where('is_primary', 1)->first() ?? $person->addresses->first();
            $assetPerson = $device->assetPerson;

            // Extract coordinates from geographical information
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

            // Build installation data
            $installation = [
                // Required fields
                'customer_external_id' => (string) $person->id,
                'manufacturer' => $deviceData->manufacturer->name ?? 'Unknown',
                'serial_number' => $deviceData->serial_number ?? '',

                // Optional device information
                'device_external_id' => (string) $device->id,
                'device_category' => $deviceCategory,
                'is_test' => $isTest,

                // Location information
                'latitude' => $latitude,
                'longitude' => $longitude,
                'country' => $primaryAddress?->city?->country?->country_code ?? null,
                'location_area_1' => $primaryAddress?->city?->country?->country_name ?? null,
                'location_area_2' => $primaryAddress?->city?->name ?? null,
                'site_name' => $primaryAddress?->street ?? null,

                // Customer information
                'usage_category' => 'household', // Default assumption
                'primary_use' => null, // Could be determined from device type or customer data

                // Device technical specifications (if available)
                'firmware_version' => null,
                'model' => null,
                'rated_power_w' => null,
                'pv_power_w' => null,
                'battery_capacity_wh' => null,
                'dc_input_source' => $deviceCategory === 'solar_home_system' ? 'solar' : null,

                // Payment plan information (from AssetPerson if available)
                'payment_plan_category' => 'paygo',
                'payment_plan_currency' => null,
                'payment_plan_cash_price' => $assetPerson?->total_cost ?? null,
                'payment_plan_amount_down_payment' => $assetPerson?->down_payment ?? null,
                'payment_plan_number_of_installments' => $assetPerson?->rate_count ?? null,
                'payment_plan_installment_amount' => null,

                // Important dates
                'installation_date' => $device->created_at->format('Y-m-d'),
                'purchase_date' => $device->created_at->format('Y-m-d'),

                // Additional fields that could be populated based on available data
                'seller_agent_external_id' => null,
                'installer_agent_external_id' => null,
                'product_common_id' => null,
                'parent_external_id' => null,
                'account_external_id' => null,
                'usage_sub_category' => null,
                'ac_input_source' => null,
                'payment_plan_amount_financed_principal' => null,
                'payment_plan_amount_financed_interest' => null,
                'payment_plan_amount_financed_total' => null,
                'payment_plan_installment_period_days' => null,
                'payment_plan_days_financed' => null,
                'payment_plan_days_down_payment' => null,
                'repossession_date' => null,
                'paid_off_date' => null,
                'repossession_category' => null,
                'write_off_date' => null,
                'write_off_reason' => null,
                'location_area_3' => null,
                'location_area_4' => null,
                'location_area_5' => null,
            ];

            $installations[] = $installation;
        }

        $this->info('Loaded '.count($installations).' installation(s) from database');

        return $installations;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultTestData(): array {
        $isTest = (bool) $this->option('test');

        return [
            [
                'customer_external_id' => 'SMU 12 Chinsanka',
                'seller_agent_external_id' => 'SMU 12 Chinsanka',
                'installer_agent_external_id' => 'SMU 12 Chinsanka',
                'product_common_id' => 'Verasol',
                'device_external_id' => '1',
                'parent_external_id' => '1',
                'account_external_id' => '1',
                'battery_capacity_wh' => 500,
                'usage_category' => 'household',
                'usage_sub_category' => 'farmer',
                'device_category' => 'solar_home_system',
                'ac_input_source' => 'generator, grid, wind turbine etc..',
                'dc_input_source' => 'solar',
                'firmware_version' => '1.2-rc3',
                'manufacturer' => 'HOP',
                'model' => 'DTZ1737',
                'primary_use' => 'cooking',
                'rated_power_w' => 30,
                'pv_power_w' => 50,
                'serial_number' => 'A1233754345JL',
                'site_name' => 'Hospital Name, Grid Name, etc',
                'payment_plan_amount_financed_principal' => 1500,
                'payment_plan_amount_financed_interest' => 1500,
                'payment_plan_amount_financed_total' => 1500,
                'payment_plan_amount_down_payment' => 1500,
                'payment_plan_cash_price' => 20000,
                'payment_plan_currency' => 'ZMW',
                'payment_plan_installment_amount' => 25000,
                'payment_plan_number_of_installments' => 365,
                'payment_plan_installment_period_days' => 180,
                'payment_plan_days_financed' => 3650,
                'payment_plan_days_down_payment' => 30,
                'payment_plan_category' => 'paygo',
                'purchase_date' => '2022-01-01',
                'installation_date' => '2022-01-01',
                'repossession_date' => '2022-01-01',
                'paid_off_date' => '2022-01-01',
                'repossession_category' => 'swap',
                'write_off_date' => '2022-01-01',
                'write_off_reason' => 'Return',
                'is_test' => $isTest,
                'latitude' => 37.775,
                'longitude' => -122.419,
                'country' => 'UG',
                'location_area_1' => 'Northern',
                'location_area_2' => 'Agago',
                'location_area_3' => 'Arum',
                'location_area_4' => 'Alela',
                'location_area_5' => 'Bila',
            ],
        ];
    }

    /**
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
