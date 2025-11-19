<?php

namespace App\Services;

use App\Exceptions\NoActiveSmsProviderException;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\SmsAndroidSetting;
use App\Sms\AndroidGateway;
use Illuminate\Support\Facades\Log;
use Inensus\AfricasTalking\AfricasTalkingGateway;
use Inensus\TextbeeSmsGateway\TextbeeSmsGateway;
use Inensus\ViberMessaging\Models\ViberContact;
use Inensus\ViberMessaging\Services\ViberContactService;
use Inensus\ViberMessaging\ViberGateway;

class SmsGatewayResolverService {
    public const VIBER_GATEWAY = 'ViberGateway';
    public const AFRICAS_TALKING_GATEWAY = 'AfricasTalkingGateway';
    public const TEXTBEE_GATEWAY = 'TextbeeSmsGateway';
    public const DEFAULT_GATEWAY = 'AndroidGateway';
    public const DEFAULT_GATEWAY_ID = 2;

    public const SMS_GATEWAY_IDS = [
        self::VIBER_GATEWAY => MpmPlugin::VIBER_MESSAGING,
        self::AFRICAS_TALKING_GATEWAY => MpmPlugin::AFRICAS_TALKING,
        self::TEXTBEE_GATEWAY => MpmPlugin::TEXTBEE_SMS_GATEWAY,
        self::DEFAULT_GATEWAY => self::DEFAULT_GATEWAY_ID,
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

        $textbeePlugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::TEXTBEE_SMS_GATEWAY);

        if ($textbeePlugin && $textbeePlugin->status == Plugins::ACTIVE) {
            $gateway = self::TEXTBEE_GATEWAY;
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

            throw new NoActiveSmsProviderException('No active SMS provider is configured. Please enable AfricasTalking, TextBee, or Viber Messaging plugin.');
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

        $textbeePlugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::TEXTBEE_SMS_GATEWAY);

        if ($textbeePlugin && $textbeePlugin->status == Plugins::ACTIVE) {
            return true;
        }

        $viberMessagingPlugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::VIBER_MESSAGING);

        return $viberMessagingPlugin && $viberMessagingPlugin->status == Plugins::ACTIVE;
    }

    /**
     * Resolve the gateway instance and prepare the arguments for sending SMS.
     *
     * @param string                                                                                                                              $gateway Gateway type (VIBER_GATEWAY, AFRICAS_TALKING_GATEWAY, TEXTBEE_GATEWAY, or DEFAULT_GATEWAY)
     * @param Sms                                                                                                                                 $sms     SMS model instance
     * @param array{body?: string, receiver?: string, viberId?: string|null, callback?: string|null, smsAndroidSettings?: SmsAndroidSetting|null} $params  Additional parameters
     *
     * @return array{gateway: ViberGateway|AfricasTalkingGateway|TextbeeSmsGateway|AndroidGateway, args: array<int, mixed>, gatewayId: int}
     */
    public function resolveGatewayAndArgs(string $gateway, Sms $sms, array $params = []): array {
        $body = $params['body'] ?? $sms->body;
        $receiver = $params['receiver'] ?? $sms->receiver;
        $viberId = $params['viberId'] ?? null;
        $callback = $params['callback'] ?? null;
        $smsAndroidSettings = $params['smsAndroidSettings'] ?? null;

        return match ($gateway) {
            self::VIBER_GATEWAY => [
                'gateway' => resolve(ViberGateway::class),
                'args' => [$body, $viberId, $sms],
                'gatewayId' => MpmPlugin::VIBER_MESSAGING,
            ],
            self::AFRICAS_TALKING_GATEWAY => [
                'gateway' => resolve(AfricasTalkingGateway::class),
                'args' => [$body, $receiver, $sms],
                'gatewayId' => MpmPlugin::AFRICAS_TALKING,
            ],
            self::TEXTBEE_GATEWAY => [
                'gateway' => resolve(TextbeeSmsGateway::class),
                'args' => [$body, $receiver, $sms],
                'gatewayId' => MpmPlugin::TEXTBEE_SMS_GATEWAY,
            ],
            default => [
                'gateway' => resolve(AndroidGateway::class),
                'args' => [
                    $receiver,
                    $body,
                    $callback ?? sprintf($smsAndroidSettings->callback ?? '', $sms->uuid),
                    $smsAndroidSettings ?? SmsAndroidSetting::getResponsible(),
                ],
                'gatewayId' => self::DEFAULT_GATEWAY_ID,
            ],
        };
    }
}
