<?php

namespace App\Services\ImportServices;

use App\Services\MainSettingsService;
use Illuminate\Support\Facades\Log;

class SettingsImportService extends AbstractImportService {
    public function __construct(
        private MainSettingsService $mainSettingsService,
    ) {}

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function import(array $data): array {
        // Handle export format: data might be wrapped in 'data' key or direct array
        $importData = $data;
        if (isset($data['data']) && is_array($data['data'])) {
            $importData = $data['data'];
        }

        // Handle export format: array with one element (settings object), or direct settings object
        $settingsData = $importData;
        if (isset($importData[0]) && is_array($importData[0])) {
            // Export format: array with one element
            $settingsData = $importData[0];
        }

        $errors = $this->validate($settingsData);
        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        try {
            $settings = $this->mainSettingsService->getAll()->first();

            if ($settings === null) {
                // Create new settings if none exist
                $settings = $this->mainSettingsService->create([
                    'site_title' => $settingsData['site_title'] ?? '',
                    'company_name' => $settingsData['company_name'] ?? '',
                    'currency' => $settingsData['currency'] ?? '€',
                    'country' => $settingsData['country'] ?? '',
                    'language' => $settingsData['language'] ?? 'en',
                    'vat_energy' => $settingsData['vat_energy'] ?? 0,
                    'vat_appliance' => $settingsData['vat_appliance'] ?? 0,
                    'usage_type' => $settingsData['usage_type'] ?? null,
                    'sms_gateway_id' => $settingsData['sms_gateway_id'] ?? null,
                ]);
            } else {
                // Update existing settings
                $updateData = [
                    'site_title' => $settingsData['site_title'] ?? $settings->site_title,
                    'company_name' => $settingsData['company_name'] ?? $settings->company_name,
                    'currency' => $settingsData['currency'] ?? $settings->currency,
                    'country' => $settingsData['country'] ?? $settings->country,
                    'language' => $settingsData['language'] ?? $settings->language,
                    'vat_energy' => $settingsData['vat_energy'] ?? $settings->vat_energy,
                    'vat_appliance' => $settingsData['vat_appliance'] ?? $settings->vat_appliance,
                ];

                if (isset($settingsData['usage_type'])) {
                    $updateData['usage_type'] = $settingsData['usage_type'] === '' ? null : $settingsData['usage_type'];
                }

                if (isset($settingsData['sms_gateway_id'])) {
                    $updateData['sms_gateway_id'] = $settingsData['sms_gateway_id'] === '' ? null : $settingsData['sms_gateway_id'];
                }

                $settings = $this->mainSettingsService->update($settings, $updateData);
            }

            return [
                'success' => true,
                'message' => 'Settings imported successfully',
                'data' => $settings->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('Error importing settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'errors' => ['import' => 'Failed to import settings: '.$e->getMessage()],
            ];
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function validate(array $data): array {
        $errors = [];

        // Settings import is flexible - most fields are optional
        // Only validate data types if provided
        if (isset($data['vat_energy']) && !is_numeric($data['vat_energy'])) {
            $errors['vat_energy'] = 'VAT energy must be a number';
        }

        if (isset($data['vat_appliance']) && !is_numeric($data['vat_appliance'])) {
            $errors['vat_appliance'] = 'VAT appliance must be a number';
        }

        if (isset($data['currency']) && !is_string($data['currency'])) {
            $errors['currency'] = 'Currency must be a string';
        }

        if (isset($data['language']) && !is_string($data['language'])) {
            $errors['language'] = 'Language must be a string';
        }

        return $errors;
    }
}
