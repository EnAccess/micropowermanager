<?php

namespace App\Http\Controllers;

use App\Http\Requests\SmsApplianceRemindRateRequest;
use App\Http\Resources\ApiResource;
use App\Models\SmsApplianceRemindRate;
use App\Services\SmsApplianceRemindRateService;
use Illuminate\Http\Request;

class SmsApplianceRemindRateController extends Controller {
    private SmsApplianceRemindRateService $smsApplianceRemindService;

    public function __construct(SmsApplianceRemindRateService $smsApplianceRemindService) {
        $this->smsApplianceRemindService = $smsApplianceRemindService;
    }

    public function index(): ApiResource {
        return new ApiResource($this->smsApplianceRemindService->getApplianceRemindRatesWithAppliances());
    }

    public function store(SmsApplianceRemindRateRequest $request): ApiResource {
        return new ApiResource($this->smsApplianceRemindService->createApplianceRemindRate($request->all()));
    }

    public function update(SmsApplianceRemindRate $smsApplianceRemindRate, Request $request): ApiResource {
        return new ApiResource($this->smsApplianceRemindService->updateApplianceRemindRate(
            $smsApplianceRemindRate,
            $request->all()
        ));
    }
}
