<?php

namespace Inensus\SafaricomMobileMoney\Services;

use Inensus\SafaricomMobileMoney\Models\SafaricomSettings;

class SafaricomMobileMoneyService {
    public function __construct(
        private SafaricomSettings $settings,
    ) {}

    public function initialize(): void {
        $this->createDefaultSettings();
    }

    private function createDefaultSettings(): void {
        $this->settings->newQuery()->firstOrCreate(['id' => 1], [
            'consumer_key' => config('safaricom-mobile-money.api.consumer_key'),
            'consumer_secret' => config('safaricom-mobile-money.api.consumer_secret'),
            'passkey' => config('safaricom-mobile-money.api.passkey'),
            'shortcode' => config('safaricom-mobile-money.api.shortcode'),
            'environment' => config('safaricom-mobile-money.api.env'),
            'validation_url' => config('safaricom-mobile-money.webhook.validation_url'),
            'confirmation_url' => config('safaricom-mobile-money.webhook.confirmation_url'),
            'timeout_url' => config('safaricom-mobile-money.webhook.timeout_url'),
            'result_url' => config('safaricom-mobile-money.webhook.result_url'),
        ]);
    }

    public function getSettings() {
        return $this->settings->newQuery()->first();
    }

    public function updateSettings($data) {
        $settings = $this->settings->newQuery()->find($data['id']);

        $settings->update([
            'consumer_key' => $data['consumer_key'],
            'consumer_secret' => $data['consumer_secret'],
            'passkey' => $data['passkey'],
            'shortcode' => $data['shortcode'],
            'environment' => $data['environment'],
            'validation_url' => $data['validation_url'],
            'confirmation_url' => $data['confirmation_url'],
            'timeout_url' => $data['timeout_url'],
            'result_url' => $data['result_url'],
        ]);
        $settings->save();

        return $settings->fresh();
    }
}
