<?php

namespace App\Plugins\AfricasTalking\Listeners;

use App\Events\SmsStoredEvent;
use App\Listeners\SmsListener as GlobalSmsListener;
use App\Plugins\AfricasTalking\Providers\AfricasTalkingServiceProvider;
use App\Services\MainSettingsService;

class SmsListener {
    public function __construct(
        private GlobalSmsListener $globalSmsListener,
        private MainSettingsService $mainSettingsService,
    ) {}

    public function handle(SmsStoredEvent $event): void {
        $mainSettings = $this->mainSettingsService->getAll()->first();

        if ($mainSettings?->sms_gateway_plugin_slug !== AfricasTalkingServiceProvider::SLUG) {
            return;
        }

        $this->globalSmsListener->handle($event);
    }
}
