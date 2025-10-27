<?php

namespace App\Services;

use Inensus\ViberMessaging\Models\ViberContact;
use App\Exceptions\NoActiveSmsProviderException;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use Illuminate\Support\Facades\Log;
use Inensus\ViberMessaging\Services\ViberContactService;

class SmsGatewayResolverService {
    public const VIBER_GATEWAY = 'ViberGateway';
    public const AFRICAS_TALKING_GATEWAY = 'AfricasTalkingGateway';
    public const DEFAULT_GATEWAY = 'AndroidGateway';

    public const SMS_GATEWAY_IDS = [
        self::VIBER_GATEWAY => MpmPlugin::VIBER_MESSAGING,
        self::AFRICAS_TALKING_GATEWAY => MpmPlugin::AFRICAS_TALKING,
        self::DEFAULT_GATEWAY => 2,
    ];

    public function __construct(
        private PluginsService $pluginsService,
        private ViberContactService $viberContactService,
    ) {}

    /**
     * Determine the appropriate gateway for the given receiver.
     *
     * @return array{0: string, 1: string|null} [gateway, viberId]
     *
     * @throws NoActiveSmsProviderException
     */
    public function determineGateway(string $receiver): array {
        $africasTalkingPlugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::AFRICAS_TALKING);
        $gateway = null;
        $viberId = null;

        if ($africasTalkingPlugin && $africasTalkingPlugin->status == Plugins::ACTIVE) {
            $gateway = self::AFRICAS_TALKING_GATEWAY;
        }

        $viberMessagingPlugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::VIBER_MESSAGING);

        if ($viberMessagingPlugin && $viberMessagingPlugin->status == Plugins::ACTIVE) {
            $viberContact = $this->viberContactService->getByReceiverPhoneNumber($receiver);

            if ($viberContact instanceof ViberContact) {
                $gateway = self::VIBER_GATEWAY;
                $viberId = $viberContact->viber_id;
            }
        }

        if ($gateway === null) {
            Log::error('No active SMS provider configured', ['receiver' => $receiver]);

            throw new NoActiveSmsProviderException('No active SMS provider is configured. Please enable AfricasTalking or Viber Messaging plugin.');
        }

        return [$gateway, $viberId];
    }

    public function getGatewayId(string $gateway): int {
        return self::SMS_GATEWAY_IDS[$gateway] ?? self::SMS_GATEWAY_IDS[self::DEFAULT_GATEWAY];
    }

    /**
     * Check if at least one SMS provider is configured and active.
     */
    public function hasActiveProvider(): bool {
        $africasTalkingPlugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::AFRICAS_TALKING);

        if ($africasTalkingPlugin && $africasTalkingPlugin->status == Plugins::ACTIVE) {
            return true;
        }

        $viberMessagingPlugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::VIBER_MESSAGING);
        return $viberMessagingPlugin && $viberMessagingPlugin->status == Plugins::ACTIVE;
    }
}
