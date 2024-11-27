<?php

namespace App\Http\Controllers;

use App\Http\Requests\SmsAndroidSettingRequest;
use App\Http\Resources\ApiResource;
use App\Models\SmsAndroidSetting;
use App\Services\SmsAndroidSettingService;

class SmsAndroidSettingController extends Controller {
    public function __construct(private SmsAndroidSettingService $smsAndroidSettingService) {
        $this->smsAndroidSettingService = $smsAndroidSettingService;
    }

    public function index(): ApiResource {
        return ApiResource::make($this->smsAndroidSettingService->getSmsAndroidSetting());
    }

    public function store(SmsAndroidSettingRequest $request): ApiResource {
        return ApiResource::make($this->smsAndroidSettingService->createSmsAndroidSetting($request->input('token')));
    }

    public function update(SmsAndroidSetting $smsAndroidSetting, SmsAndroidSettingRequest $request): ApiResource {
        return ApiResource::make($this->smsAndroidSettingService->updateSmsAndroidSetting(
            $smsAndroidSetting,
            $request->input('token')
        ));
    }

    public function destroy(SmsAndroidSetting $smsAndroidSetting): ApiResource {
        return ApiResource::make($this->smsAndroidSettingService->deleteSmsAndroidSetting($smsAndroidSetting));
    }
}
