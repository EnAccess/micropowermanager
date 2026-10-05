<?php

namespace App\Http\Controllers;

use App\Http\Requests\SmsApplianceRemindRateRequest;
use App\Http\Resources\ApiResource;
use App\Models\SmsApplianceRemindRate;
use App\Services\SmsApplianceRemindRateService;

class SmsApplianceRemindRateController extends Controller {
    public function __construct(private SmsApplianceRemindRateService $smsApplianceRemindService) {}

    public function index(): ApiResource {
        return new ApiResource($this->smsApplianceRemindService->getApplianceRemindRatesWithAppliances());
    }

    public function store(SmsApplianceRemindRateRequest $request): ApiResource {
        return new ApiResource($this->smsApplianceRemindService->createApplianceRemindRate($request->validated()));
    }

    public function update(SmsApplianceRemindRate $smsApplianceRemindRate, SmsApplianceRemindRateRequest $request): ApiResource {
        return new ApiResource($this->smsApplianceRemindService->updateApplianceRemindRate(
            $smsApplianceRemindRate,
            $request->validated()
        ));
    }

    public function destroy(SmsApplianceRemindRate $smsApplianceRemindRate): ApiResource {
        return new ApiResource($this->smsApplianceRemindService->deleteApplianceRemindRate($smsApplianceRemindRate));
    }
}
