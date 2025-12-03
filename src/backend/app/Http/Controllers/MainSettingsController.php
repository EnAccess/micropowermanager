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
        return ApiResource::make($this->mainSettingsService->getAll()->first());
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

        $updated = $this->mainSettingsService->update($mainSettings, $mainSettingsData);

        return ApiResource::make($updated);
    }

    public function getAvailableSmsGateways(): ApiResource {
        $availableGateways = $this->smsGatewayResolverService->getAvailableSmsGateways();

        return ApiResource::make($availableGateways);
    }
}
