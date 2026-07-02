<?php

namespace App\Services\ImportServices;

use App\Services\MainSettingsService;

/**
 * @extends AbstractImportService<SettingsImportItem>
 */
class SettingsImportService extends AbstractImportService {
    public function __construct(
        private MainSettingsService $mainSettingsService,
    ) {}

    /**
     * @param list<SettingsImportItem> $data
     */
    public function import(array $data): ImportResult {
        $item = $data[0];

        try {
            $settings = $this->mainSettingsService->getAll()->first();
            $isNew = $settings === null;

            if ($isNew) {
                // Create new settings if none exist
                $settings = $this->mainSettingsService->create([
                    'site_title' => $item->siteTitle ?? '',
                    'company_name' => $item->companyName ?? '',
                    'currency' => $item->currency ?? '€',
                    'country' => $item->country ?? '',
                    'language' => $item->language ?? 'en',
                    'vat_energy' => $item->vatEnergy ?? 0,
                    'vat_appliance' => $item->vatAppliance ?? 0,
                    'usage_type' => $item->usageType,
                    'sms_gateway_id' => $item->smsGatewayId,
                    'transaction_sms_enabled' => $item->transactionSmsEnabled ?? true,
                ]);
            } else {
                // Update existing settings
                $updateData = [
                    'site_title' => $item->siteTitle ?? $settings->site_title,
                    'company_name' => $item->companyName ?? $settings->company_name,
                    'currency' => $item->currency ?? $settings->currency,
                    'country' => $item->country ?? $settings->country,
                    'language' => $item->language ?? $settings->language,
                    'vat_energy' => $item->vatEnergy ?? $settings->vat_energy,
                    'vat_appliance' => $item->vatAppliance ?? $settings->vat_appliance,
                ];

                if ($item->usageType !== null) {
                    $updateData['usage_type'] = $item->usageType === '' ? null : $item->usageType;
                }

                if ($item->smsGatewayId !== null) {
                    $updateData['sms_gateway_id'] = $item->smsGatewayId === '' ? null : $item->smsGatewayId;
                }

                if ($item->transactionSmsEnabled !== null) {
                    $updateData['transaction_sms_enabled'] = $item->transactionSmsEnabled;
                }

                $settings = $this->mainSettingsService->update($settings, $updateData);
            }

            return new ImportResult(
                message: 'Settings imported successfully',
                added: $isNew ? [$settings->toArray()] : [],
                modified: $isNew ? [] : [$settings->toArray()],
                failed: [],
            );
        } catch (\Exception $e) {
            $this->throwTransactionFailure('settings', $e);
        }
    }
}
