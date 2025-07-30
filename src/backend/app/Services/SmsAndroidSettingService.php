<?php

namespace App\Services;

use App\Models\SmsAndroidSetting;
use Illuminate\Database\Eloquent\Collection;

class SmsAndroidSettingService {
    private string $fireBaseKey;
    private string $callbackUrl;

    public function __construct(
        private SmsAndroidSetting $smsAndroidSetting,
        private UserService $userService,
    ) {}

    /**
     * @return Collection<int, SmsAndroidSetting>
     */
    public function getSmsAndroidSetting(): Collection {
        return $this->smsAndroidSetting->newQuery()->get();
    }

    /**
     * @return Collection<int, SmsAndroidSetting>
     */
    public function createSmsAndroidSetting(string $androidPhoneToken): Collection {
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

    /**
     * @return Collection<int, SmsAndroidSetting>
     */
    public function updateSmsAndroidSetting(SmsAndroidSetting $smsAndroidSetting, string $androidPhoneToken): Collection {
        $this->fireBaseKey = config('services.sms.android.key');
        $this->callbackUrl = config('services.sms.android.callback_url').$this->userService->getCompanyId();

        $smsAndroidSetting->update([
            'callback' => $this->callbackUrl,
            'token' => $androidPhoneToken,
            'key' => $this->fireBaseKey,
        ]);

        return $this->smsAndroidSetting->newQuery()->get();
    }

    /**
     * @return Collection<int, SmsAndroidSetting>
     */
    public function deleteSmsAndroidSetting(SmsAndroidSetting $smsAndroidSetting): Collection {
        $smsAndroidSetting->delete();

        return $this->smsAndroidSetting->newQuery()->get();
    }
}
