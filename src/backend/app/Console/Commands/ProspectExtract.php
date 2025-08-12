<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Database\Eloquent\Model;
use App\Console\Commands\AbstractSharedCommand;

class ProspectExtract extends AbstractSharedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:extract
                            {--company-id= : The tenant ID to run the command for (defaults to current tenant)}
                            {--limit= : Limit number of records when loading from database}
                            {--test : Mark data as test data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract installation data from database for Prospect';



    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $this->info('Starting Prospect data extraction...');

            $data = $this->extractDataFromDatabase();

            if (empty($data)) {
                $this->error('No data found to extract.');
                return 1;
            }

            $count = count($data);
            $this->info('Successfully extracted ' . $count . ' ' . ($count === 1 ? 'installation' : 'installations') . ' from database');

            // For now, just show the count - CSV generation will come next
            $this->line('Data extracted successfully. CSV generation will be implemented next.');

            return 0;

        } catch (\Exception $e) {
            $this->error('Error during extraction: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Extract data from database
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractDataFromDatabase(): array
    {
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

            $deviceData->load('manufacturer');

            $person = $device->person;
            $primaryAddress = null;
            $assetPerson = $device->assetPerson;

            if ($person && $person->addresses) {
                $primaryAddress = $person->addresses->where('is_primary', 1)->first() ?? $person->addresses->first();
            }

            // Create meaningful customer identifier
            $customerIdentifier = $person ? trim($person->name . ' ' . $person->surname) : 'Unknown Customer';


            $primaryAddress = null;
            if ($person && $person->addresses) {
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

                'is_test' => $isTest,
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
        $this->info('Loaded ' . $count . ' ' . ($count === 1 ? 'installation' : 'installations') . ' from database');

        return $installations;
    }
}
