<?php

namespace App\Services;

use App\Models\SmsAndroidSetting;

class SmsAndroidSettingService {
    private string $fireBaseKey;
    private string $callbackUrl;

    public function __construct(
        private SmsAndroidSetting $smsAndroidSetting,
        private UserService $userService,
    ) {}

    public function getSmsAndroidSetting() {
        return $this->smsAndroidSetting->newQuery()->get();
    }

    public function createSmsAndroidSetting($androidPhoneToken) {
        $this->fireBaseKey = config('services.sms.android.key');
        $this->callbackUrl = config('services.sms.callback').$this->userService->getCompanyId();

        $smsAndroidSettingData = [
            'callback' => $this->callbackUrl,
            'token' => $androidPhoneToken,
            'key' => $this->fireBaseKey,
        ];
        $this->smsAndroidSetting->newQuery()->create($smsAndroidSettingData);

        return $this->smsAndroidSetting->newQuery()->get();
    }

    public function updateSmsAndroidSetting(SmsAndroidSetting $smsAndroidSetting, $androidPhoneToken) {
        $this->fireBaseKey = config('services.sms.android.key');
        $this->callbackUrl = config('services.sms.android.callback_url').$this->userService->getCompanyId();

        $smsAndroidSetting->update([
            'callback' => $this->callbackUrl,
            'token' => $androidPhoneToken,
            'key' => $this->fireBaseKey,
        ]);

        return $this->smsAndroidSetting->newQuery()->get();
    }

    public function deleteSmsAndroidSetting(SmsAndroidSetting $smsAndroidSetting) {
        $smsAndroidSetting->delete();

        return $this->smsAndroidSetting->newQuery()->get();
    }
}
