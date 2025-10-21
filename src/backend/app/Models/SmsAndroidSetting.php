<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\SmsAndroidSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @property int $id
 */
class SmsAndroidSetting extends BaseModel {
    /** @use HasFactory<SmsAndroidSettingFactory> */
    use HasFactory;
    protected $table = 'sms_android_settings';

    public static function getResponsible(): ?self {
        $smsAndroidSettings = SmsAndroidSetting::all();
        if ($smsAndroidSettings->count()) {
            try {
                $lastSms = Sms::query()->latest()->select('id')->take(1)->firstOrFail()->id;
                $responsibleGateway = $smsAndroidSettings[$lastSms % $smsAndroidSettings->count()];
            } catch (ModelNotFoundException) {
                $responsibleGateway = $smsAndroidSettings[0];
            }

            return $responsibleGateway;
        }

        return null;
    }

    public function getId(): int {
        return $this->id;
    }
}
