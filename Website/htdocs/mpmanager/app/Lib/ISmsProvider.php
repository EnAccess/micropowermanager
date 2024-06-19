<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 08.06.18
 * Time: 16:39.
 */

namespace App\Lib;

use App\Models\SmsAndroidSetting;

interface ISmsProvider
{
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
