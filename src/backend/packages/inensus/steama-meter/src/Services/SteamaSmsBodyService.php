<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Models\SteamaSmsBody;

class SteamaSmsBodyService {
    private $smsBody;

    public function __construct(SteamaSmsBody $smsBody) {
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
                'reference' => 'SteamaSmsLowBalanceHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SteamaSmsLowBalanceBody',
                'place_holder' => 'your credit balance has reduced under [low_balance_warning],'
                    .'your currently balance is [account_balance]',
                'variables' => 'low_balance_warning,account_balance',
                'title' => 'Low Balance Limit Notify',
            ],
            [
                'reference' => 'SteamaSmsBalanceFeedbackHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SteamaSmsBalanceFeedbackBody',
                'place_holder' => 'your currently balance is [account_balance]',
                'variables' => 'account_balance',
                'title' => 'Balance Feedback',
            ],
            [
                'reference' => 'SteamaSmsBalanceFeedbackFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SteamaSmsLowBalanceFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
        ];
        collect($smsBodies)->each(function ($smsBody) {
            $this->smsBody->newQuery()->firstOrCreate(['reference' => $smsBody['reference']], $smsBody);
        });
    }
}
