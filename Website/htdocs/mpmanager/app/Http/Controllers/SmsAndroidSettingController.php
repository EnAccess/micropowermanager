<?php

namespace App\Http\Controllers;

use App\Http\Requests\SmsAndroidSettingRequest;
use App\Http\Resources\ApiResource;
use App\Models\SmsAndroidSetting;
use App\Services\SmsAndroidSettingService;

class SmsAndroidSettingController extends Controller
{
    private $smsAndroidSettingService;

    public function __construct(SmsAndroidSettingService $smsAndroidSettingService)
    {
        $this->smsAndroidSettingService = $smsAndroidSettingService;
    }

    public function index(): ApiResource
    {
        return new ApiResource($this->smsAndroidSettingService->getSmsAndroidSetting());
    }

    public function store(SmsAndroidSettingRequest $request): ApiResource
    {
        return new ApiResource($this->smsAndroidSettingService->createSmsAndroidSetting($request->all()));
    }

    public function update(SmsAndroidSetting $smsAndroidSetting, SmsAndroidSettingRequest $request)
    {
        return new ApiResource($this->smsAndroidSettingService->updateSmsAndroidSetting(
            $smsAndroidSetting,
            $request->only(['token', 'key', 'callback'])
        ));
    }

    public function destroy(SmsAndroidSetting $smsAndroidSetting): ApiResource
    {
        return new ApiResource($this->smsAndroidSettingService->deleteSmsAndroidSetting($smsAndroidSetting));
    }
}
