<?php

namespace App\Lib;

use App\Models\SmsAndroidSetting;

interface ISmsProvider {
    /**
     * Sends the sms to the sms provider.
     *
     * @param string            $number
     * @param string            $body
     * @param string            $callback
     * @param SmsAndroidSetting $smsAndroidSetting
     *
     * @return mixed
     */
    public function sendSms(string $number, string $body, string $callback, SmsAndroidSetting $smsAndroidSetting);
}
