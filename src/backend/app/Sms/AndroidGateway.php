<?php

namespace App\Sms;

use App\Jobs\SmsLoadBalancer;
use App\Lib\ISmsProvider;
use App\Models\SmsAndroidSetting;
use Illuminate\Support\Facades\Log;

class AndroidGateway implements ISmsProvider {
    /**
     * Sends the sms to the sms provider.
     *
     * @param string            $number
     * @param string            $body
     * @param string            $callback
     * @param SmsAndroidSetting $smsAndroidSetting
     */
    public function sendSms(string $number, string $body, string $callback, SmsAndroidSetting $smsAndroidSetting) {
        if (!config('app.env') == 'production') {
            Log::debug('Send sms on allowed in production', ['number' => $number, 'message' => $body]);

            return;
        }
        try {
            $callbackWithoutProtocolRoot = explode('micropowermanager.com/api/', $callback)[1];
        } catch (\Exception $e) {
            Log::error(
                'Error while sending sms',
                ['number' => $number, 'message' => $body, 'error' => $e->getMessage()]
            );

            throw new \Exception('Error while sending sms');
        }

        // add sms to sms_gateway job
        SmsLoadBalancer::dispatch([
            'number' => $number,
            'message' => $body,
            'callback' => $callbackWithoutProtocolRoot,
            'setting' => $smsAndroidSetting,
        ])->onConnection('redis')->onQueue('sms_gateway');
    }
}
