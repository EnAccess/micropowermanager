<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MainSettings;
use App\Services\ExportServices\SettingsExportService;
use Tests\TestCase;

class SettingsExportServiceTest extends TestCase {
    public function testTheExportCarriesTheDownPaymentTokenLimit(): void {
        $settings = MainSettings::query()->firstOrFail();
        $settings->update(['down_payment_max_token_days' => 14]);

        $service = resolve(SettingsExportService::class);
        $service->setSettingsData($settings->fresh());

        $this->assertSame(14, $service->exportDataToArray()[0]['down_payment_max_token_days']);
    }

    /**
     * The xlsx export writes values positionally under a header row that lives in the template,
     * so the two drift apart silently unless the column count is pinned.
     */
    public function testTheExportedColumnSetMatchesTheTemplateHeaderRow(): void {
        $service = resolve(SettingsExportService::class);
        $service->setSettingsData(MainSettings::query()->firstOrFail());

        $this->assertSame([
            'site_title',
            'company_name',
            'currency',
            'country',
            'language',
            'vat_energy',
            'vat_appliance',
            'usage_type',
            'sms_gateway_id',
            'transaction_sms_enabled',
            'down_payment_max_token_days',
            'created_at',
            'updated_at',
        ], array_keys($service->exportDataToArray()[0]));
    }
}
