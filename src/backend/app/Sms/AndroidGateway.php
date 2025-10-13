<?php

namespace App\Sms;

use App\Jobs\SmsLoadBalancer;
use App\Lib\ISmsProvider;
use App\Models\SmsAndroidSetting;
use Illuminate\Support\Facades\Log;

class AndroidGateway implements ISmsProvider {
    /**
     * Sends the sms to the sms provider.
     */
    public function sendSms(string $number, string $body, string $callback, SmsAndroidSetting $smsAndroidSetting): void {
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

            throw new \Exception('Error while sending sms', $e->getCode(), $e);
        }

        // add sms to sms_gateway job
        SmsLoadBalancer::dispatch([
            'number' => $number,
            'message' => $body,
            'callback' => $callbackWithoutProtocolRoot,
            'setting' => $smsAndroidSetting,
        ]);
    }
}
