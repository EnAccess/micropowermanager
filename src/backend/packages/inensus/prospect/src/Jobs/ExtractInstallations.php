<?php

namespace Inensus\Prospect\Jobs;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExtractInstallations implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'prospect_extract';

    public function handle(): void {
        try {
            Log::info('Prospect: starting extraction');

            $data = $this->extractDataFromDatabase();
            if (empty($data)) {
                Log::warning('Prospect: no data to extract');
                return;
            }

            $fileName = $this->generateFileName();
            $filePath = $this->writeCsvFile($data, $fileName);

            Log::info('Prospect: extraction done', ['file' => $filePath]);
        } catch (\Exception $e) {
            Log::error('Prospect: extraction error '.$e->getMessage());
            throw $e;
        }
    }

    private function extractDataFromDatabase(): array {
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
            if ($primaryAddress && $primaryAddress->geo && $primaryAddress->geo->points) {
                $coordinates = explode(',', $primaryAddress->geo->points);
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
                'manufacturer' => ($deviceData->manufacturer !== null) ? $deviceData->manufacturer->name : 'Unknown',
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
                'purchase_date' => $device->created_at?->format('Y-m-d'),
                'installation_date' => $device->created_at?->format('Y-m-d'),
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

        return $installations;
    }

    private function generateFileName(): string {
        $timestamp = now()->toISOString();
        return "prospect_{$timestamp}.csv";
    }

    private function writeCsvFile(array $data, string $fileName): string {
        $headers = array_keys($data[0]);
        $csvContent = $this->arrayToCsv($data, $headers);

        $companyDatabase = app(\App\Services\CompanyDatabaseService::class)->findByCompanyId(null);
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


