<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\MainSettings;
use App\Services\MainSettingsService;
use App\Services\SmsGatewayResolverService;
use Illuminate\Http\Request;

class MainSettingsController extends Controller {
    public function __construct(
        private MainSettingsService $mainSettingsService,
        private SmsGatewayResolverService $smsGatewayResolverService,
    ) {}

    public function index(): ApiResource {
        $mainSettings = $this->mainSettingsService->getAll()->first();
        if ($mainSettings) {
            unset($mainSettings['protected_page_password']);
        }

        return ApiResource::make($mainSettings);
    }

    public function update(MainSettings $mainSettings, Request $request): ApiResource {
        $mainSettingsData = $request->only([
            'site_title',
            'company_name',
            'currency',
            'country',
            'language',
            'vat_energy',
            'vat_appliance',
            'usage_type',
            'sms_gateway_id',
        ]);

        $protectedPagePassword = $request->input('protected_page_password');
        if ($protectedPagePassword) {
            $mainSettingsData['protected_page_password'] = $protectedPagePassword;
        }

        $updated = $this->mainSettingsService->update($mainSettings, $mainSettingsData);
        unset($updated['protected_page_password']);

        return ApiResource::make($updated);
    }

    public function getAvailableSmsGateways(): ApiResource {
        $availableGateways = $this->smsGatewayResolverService->getAvailableSmsGateways();

        return ApiResource::make($availableGateways);
    }
}
