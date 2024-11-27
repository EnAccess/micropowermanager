<?php

namespace Inensus\SparkMeter\Services;

use Inensus\SparkMeter\Models\SmSmsBody;

class SmSmsBodyService {
    private $smsBody;

    public function __construct(SmSmsBody $smsBody) {
        $this->smsBody = $smsBody;
    }

    public function getSmsBodyByReference($reference) {
        return $this->smsBody->newQuery()->where('reference', $reference)->firstOrFail();
    }

    public function getSmsBodies() {
        return $this->smsBody->newQuery()->get();
    }

    public function updateSmsBodies($smsBodiesData) {
        $smsBodies = $this->smsBody->newQuery()->get();
        collect($smsBodiesData)->each(function ($smsBody) use ($smsBodies) {
            $smsBodies->filter(function ($body) use ($smsBody) {
                return $body['id'] === $smsBody['id'];
            })->first()->update([
                'body' => $smsBody['body'],
            ]);
        });

        return $smsBodies;
    }

    public function getNullBodies() {
        return $this->smsBody->newQuery()->whereNull('body')->get();
    }

    public function createSmsBodies() {
        $smsBodies = [
            [
                'reference' => 'SparkSmsLowBalanceHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SparkSmsLowBalanceBody',
                'place_holder' => 'your credit balance has reduced under [low_balance_limit],'
                    .'your currently balance is [credit_balance]',
                'variables' => 'low_balance_limit,credit_balance',
                'title' => 'Low Balance Limit Notify',
            ],
            [
                'reference' => 'SparkSmsBalanceFeedbackHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SparkSmsBalanceFeedbackBody',
                'place_holder' => 'your currently balance is [credit_balance]',
                'variables' => 'credit_balance',
                'title' => 'Balance Feedback',
            ],
            [
                'reference' => 'SparkSmsMeterResetFeedbackHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SparkSmsMeterResetFeedbackBody',
                'place_holder' => 'your meter, [meter_serial] has reset successfully.',
                'variables' => 'meter_serial',
                'title' => 'Meter Reset Feedback',
            ],
            [
                'reference' => 'SparkSmsMeterResetFailedFeedbackBody',
                'place_holder' => 'meter reset failed with [meter_serial].',
                'variables' => 'meter_serial',
                'title' => 'Meter Reset Failed Feedback',
            ],
            [
                'reference' => 'SparkSmsMeterResetFeedbackFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SparkSmsLowBalanceFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SparkSmsBalanceFeedbackFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
        ];
        collect($smsBodies)->each(function ($smsBody) {
            $this->smsBody->newQuery()->firstOrCreate(
                ['reference' => $smsBody['reference']],
                $smsBody
            );
        });
    }
}
