<?php

namespace App\Services;

use App\Enums\MpmPlugin;
use App\Exceptions\NoActiveSmsProviderException;
use App\Models\Sms;
use App\Plugins\AfricasTalking\AfricasTalkingGateway;
use App\Plugins\TextbeeSmsGateway\TextbeeSmsGateway;
use App\Plugins\ViberMessaging\Models\ViberContact;
use App\Plugins\ViberMessaging\Services\ViberContactService;
use App\Plugins\ViberMessaging\ViberGateway;
use Illuminate\Support\Facades\Log;

class SmsGatewayResolverService {
    private const array SMS_GATEWAY_PLUGINS = [
        MpmPlugin::AfricasTalking,
        MpmPlugin::TextbeeSmsGateway,
        MpmPlugin::ViberMessaging,
    ];

    public function __construct(
        private PluginsService $pluginsService,
        private ViberContactService $viberContactService,
        private MainSettingsService $mainSettingsService,
    ) {}

    /**
     * SMS gateways the tenant can choose from, i.e. its active SMS gateway plugins.
     *
     * @return list<array{slug: string, display_name: string}>
     */
    public function getAvailableSmsGateways(): array {
        $activeGateways = array_filter(
            self::SMS_GATEWAY_PLUGINS,
            $this->pluginsService->isPluginActive(...),
        );

        return array_values(array_map(
            fn (MpmPlugin $gateway): array => ['slug' => $gateway->value, 'display_name' => $gateway->displayName()],
            $activeGateways,
        ));
    }

    /**
     * Determine the gateway for the given receiver from the one configured in main settings.
     *
     * @return array{0: MpmPlugin, 1: string|null} [gateway, viberId]
     *
     * @throws NoActiveSmsProviderException
     */
    public function determineGateway(string $receiver): array {
        $gateway = $this->configuredGateway();
        $viberId = null;

        if ($gateway === MpmPlugin::ViberMessaging) {
            $viberContact = $this->viberContactService->getByReceiverPhoneNumber($receiver);
            if (!$viberContact instanceof ViberContact) {
                Log::warning('Receiver does not have Viber contact, falling back to alternative gateway', [
                    'receiver' => $receiver,
                ]);
                $gateway = null;
            } else {
                $viberId = $viberContact->viber_id;
            }
        }

        if (!$gateway instanceof MpmPlugin) {
            Log::warning('No active SMS provider configured', ['receiver' => $receiver]);

            throw new NoActiveSmsProviderException('No active SMS provider is configured. Please configure an SMS gateway in Main Settings or enable a plugin (AfricasTalking, TextBee, or Viber Messaging).');
        }

        return [$gateway, $viberId];
    }

    public function isSmsGatewayConfigured(): bool {
        try {
            return $this->configuredGateway() instanceof MpmPlugin;
        } catch (\Exception $e) {
            Log::error('Error while checking if SMS gateway is configured', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Resolve the gateway instance and prepare the arguments for sending SMS.
     *
     * @param array{body?: string, receiver?: string, viberId?: string|null} $params
     *
     * @return array{gateway: ViberGateway|AfricasTalkingGateway|TextbeeSmsGateway, args: array<int, mixed>}
     */
    public function resolveGatewayAndArgs(MpmPlugin $gateway, Sms $sms, array $params = []): array {
        $body = $params['body'] ?? $sms->body;
        $receiver = $params['receiver'] ?? $sms->receiver;
        $viberId = $params['viberId'] ?? null;

        return match ($gateway) {
            MpmPlugin::ViberMessaging => [
                'gateway' => resolve(ViberGateway::class),
                'args' => [$body, $viberId, $sms],
            ],
            MpmPlugin::AfricasTalking => [
                'gateway' => resolve(AfricasTalkingGateway::class),
                'args' => [$body, $receiver, $sms],
            ],
            MpmPlugin::TextbeeSmsGateway => [
                'gateway' => resolve(TextbeeSmsGateway::class),
                'args' => [$body, $receiver, $sms],
            ],
            default => throw new \InvalidArgumentException("Unsupported SMS gateway: {$gateway->value}"),
        };
    }

    private function configuredGateway(): ?MpmPlugin {
        $gateway = $this->mainSettingsService->getAll()->first()?->sms_gateway;

        return in_array($gateway, self::SMS_GATEWAY_PLUGINS, true) ? $gateway : null;
    }
}
