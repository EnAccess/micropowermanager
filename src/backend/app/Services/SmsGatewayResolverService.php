<?php

namespace App\Services;

use App\Exceptions\NoActiveSmsProviderException;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\SmsAndroidSetting;
use App\Plugins\AfricasTalking\AfricasTalkingGateway;
use App\Plugins\TextbeeSmsGateway\TextbeeSmsGateway;
use App\Plugins\ViberMessaging\Models\ViberContact;
use App\Plugins\ViberMessaging\Services\ViberContactService;
use App\Plugins\ViberMessaging\ViberGateway;
use App\Sms\AndroidGateway;
use Illuminate\Support\Facades\Log;

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
        private MainSettingsService $mainSettingsService,
    ) {}

    /**
     * Get available SMS gateways based on active plugins.
     *
     * @return array<int, array{id: int, name: string, label: string, is_active: bool}>
     */
    public function getAvailableSmsGateways(): array {
        $gateways = [];

        // Always include AndroidGateway (default, legacy)
        $gateways[] = [
            'id' => self::DEFAULT_GATEWAY_ID,
            'name' => self::DEFAULT_GATEWAY,
            'label' => 'Android Gateway (DEPRECATED)',
            'is_active' => true,
        ];

        // Check AfricasTalking
        $africasTalkingPlugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::AFRICAS_TALKING);
        if ($africasTalkingPlugin && $africasTalkingPlugin->status == Plugins::ACTIVE) {
            $gateways[] = [
                'id' => MpmPlugin::AFRICAS_TALKING,
                'name' => self::AFRICAS_TALKING_GATEWAY,
                'label' => "Africa's Talking",
                'is_active' => true,
            ];
        }

        // Check TextBee
        $textbeePlugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::TEXTBEE_SMS_GATEWAY);
        if ($textbeePlugin && $textbeePlugin->status == Plugins::ACTIVE) {
            $gateways[] = [
                'id' => MpmPlugin::TEXTBEE_SMS_GATEWAY,
                'name' => self::TEXTBEE_GATEWAY,
                'label' => 'TextBee SMS Gateway',
                'is_active' => true,
            ];
        }

        // Check Viber
        $viberMessagingPlugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::VIBER_MESSAGING);
        if ($viberMessagingPlugin && $viberMessagingPlugin->status == Plugins::ACTIVE) {
            $gateways[] = [
                'id' => MpmPlugin::VIBER_MESSAGING,
                'name' => self::VIBER_GATEWAY,
                'label' => 'Viber Messaging',
                'is_active' => true,
            ];
        }

        return $gateways;
    }

    /**
     * Determine the appropriate gateway for the given receiver.
     * Uses the configured gateway from main settings, with fallback to legacy behavior.
     *
     * @return array{0: string, 1: string|null} [gateway, viberId]
     *
     * @throws NoActiveSmsProviderException
     */
    public function determineGateway(string $receiver): array {
        $gateway = null;
        $viberId = null;

        // Get the configured gateway from main settings
        $mainSettings = $this->mainSettingsService->getAll()->first();
        $configuredGatewayId = $mainSettings?->sms_gateway_id;

        // If a gateway is configured in settings, use it
        if ($configuredGatewayId) {
            $gateway = $this->getGatewayNameById($configuredGatewayId);

            // Special handling for Viber: check if receiver has Viber contact
            if ($gateway === self::VIBER_GATEWAY) {
                $viberContact = $this->viberContactService->getByReceiverPhoneNumber($receiver);
                if (!($viberContact instanceof ViberContact)) {
                    // Fall back to next available gateway if receiver doesn't have Viber
                    Log::warning('Receiver does not have Viber contact, falling back to alternative gateway', [
                        'receiver' => $receiver,
                    ]);
                    $gateway = null;
                } else {
                    $viberId = $viberContact->viber_id;
                }
            }
        }

        if ($gateway === null) {
            Log::error('No active SMS provider configured', ['receiver' => $receiver]);

            throw new NoActiveSmsProviderException('No active SMS provider is configured. Please configure an SMS gateway in Main Settings or enable a plugin (AfricasTalking, TextBee, or Viber Messaging).');
        }

        return [$gateway, $viberId];
    }

    public function isSmsGatewayConfigured(): bool {
        try {
            // Get the configured gateway from main settings
            $mainSettings = $this->mainSettingsService->getAll()->first();
            $configuredGatewayId = $mainSettings?->sms_gateway_id;

            return (bool) $configuredGatewayId;
        } catch (\Exception $e) {
            Log::error('Error while checking if SMS gateway is configured', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Get gateway name by ID.
     */
    private function getGatewayNameById(int $gatewayId): ?string {
        return match ($gatewayId) {
            self::DEFAULT_GATEWAY_ID => self::DEFAULT_GATEWAY,
            MpmPlugin::VIBER_MESSAGING => self::VIBER_GATEWAY,
            MpmPlugin::AFRICAS_TALKING => self::AFRICAS_TALKING_GATEWAY,
            MpmPlugin::TEXTBEE_SMS_GATEWAY => self::TEXTBEE_GATEWAY,
            default => null,
        };
    }

    public function getGatewayId(string $gateway): int {
        return self::SMS_GATEWAY_IDS[$gateway] ?? self::SMS_GATEWAY_IDS[self::DEFAULT_GATEWAY];
    }

    /**
     * Check if at least one SMS provider is configured and active.
     */
    public function hasActiveProvider(): bool {
        $availableGateways = $this->getAvailableSmsGateways();

        return count($availableGateways) > 0;
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
