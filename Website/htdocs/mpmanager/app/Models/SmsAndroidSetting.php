<?php

namespace App\Models;

use App\Exceptions\SmsAndroidSettingNotExistingException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @property int $id
 */
class SmsAndroidSetting extends BaseModel
{
    protected $table = 'sms_android_settings';

    public static function getResponsible()
    {
        $smsAndroidSettings = SmsAndroidSetting::all();
        if ($smsAndroidSettings->count()) {
            try {
                $lastSms = Sms::query()->latest()->select('id')->take(1)->firstOrFail()->id;
                $responsibleGateway = $smsAndroidSettings[$lastSms % $smsAndroidSettings->count()];
            } catch (ModelNotFoundException $e) {
                $responsibleGateway = $smsAndroidSettings[0];
            }
            return $responsibleGateway;
        } else {
            throw new SmsAndroidSettingNotExistingException('No SMS android setting registered.');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }
}
