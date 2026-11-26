<?php

namespace App\Plugins\TextbeeSmsGateway\Listeners;

use App\Events\SmsStoredEvent;
use App\Listeners\SmsListener as GlobalSmsListener;
use App\Plugins\TextbeeSmsGateway\Providers\TextbeeSmsGatewayServiceProvider;
use App\Services\MainSettingsService;

class SmsListener {
    public function __construct(
        private GlobalSmsListener $globalSmsListener,
        private MainSettingsService $mainSettingsService,
    ) {}

    public function handle(SmsStoredEvent $event): void {
        $mainSettings = $this->mainSettingsService->getAll()->first();

        if ($mainSettings?->sms_gateway !== TextbeeSmsGatewayServiceProvider::SLUG) {
            return;
        }

        $this->globalSmsListener->handle($event);
    }
}
