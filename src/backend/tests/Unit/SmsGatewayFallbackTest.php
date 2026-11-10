<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Plugins\AfricasTalking\AfricasTalkingGateway;
use App\Plugins\ViberMessaging\Services\ViberContactService;
use App\Plugins\WhatsappBusinessApi\Exceptions\MessageNotSentException;
use App\Plugins\WhatsappBusinessApi\WhatsappGateway;
use App\Services\MainSettingsService;
use App\Services\PluginsService;
use App\Services\SmsGatewayResolverService;
use App\Services\SmsService;
use App\Sms\Senders\SmsSender;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class SmsGatewayFallbackTest extends TestCase {
    private SmsGatewayResolverService $resolverService;
    private PluginsService&MockObject $pluginsService;
    private MainSettingsService&MockObject $mainSettingsService;

    protected function setUp(): void {
        parent::setUp();

        $this->pluginsService = $this->createMock(PluginsService::class);
        $viberContactService = $this->createMock(ViberContactService::class);
        $this->mainSettingsService = $this->createMock(MainSettingsService::class);

        $this->resolverService = new SmsGatewayResolverService(
            $this->pluginsService,
            $viberContactService,
            $this->mainSettingsService,
        );
    }

    public function testFallbackPrefersAfricasTalkingWhenActive(): void {
        $africasTalking = new Plugins([
            'mpm_plugin_id' => MpmPlugin::AFRICAS_TALKING,
            'status' => Plugins::ACTIVE,
        ]);
        $textbee = new Plugins([
            'mpm_plugin_id' => MpmPlugin::TEXTBEE_SMS_GATEWAY,
            'status' => Plugins::ACTIVE,
        ]);

        $this->pluginsService->method('getByMpmPluginId')
            ->willReturnCallback(function (int $id) use ($africasTalking, $textbee): ?Plugins {
                return match ($id) {
                    MpmPlugin::AFRICAS_TALKING => $africasTalking,
                    MpmPlugin::TEXTBEE_SMS_GATEWAY => $textbee,
                    default => null,
                };
            });

        $fallback = $this->resolverService->getFallbackSmsGateway();
        $this->assertSame(SmsGatewayResolverService::AFRICAS_TALKING_GATEWAY, $fallback);
    }

    public function testFallbackSelectsTextbeeWhenAfricasTalkingIsDisabled(): void {
        $textbee = new Plugins([
            'mpm_plugin_id' => MpmPlugin::TEXTBEE_SMS_GATEWAY,
            'status' => Plugins::ACTIVE,
        ]);

        $this->pluginsService->method('getByMpmPluginId')
            ->willReturnCallback(function (int $id) use ($textbee): ?Plugins {
                return match ($id) {
                    MpmPlugin::TEXTBEE_SMS_GATEWAY => $textbee,
                    default => null,
                };
            });

        $fallback = $this->resolverService->getFallbackSmsGateway();
        $this->assertSame(SmsGatewayResolverService::TEXTBEE_GATEWAY, $fallback);
    }

    public function testFallbackReturnsNullWhenNoCellularSmsGatewayIsActive(): void {
        $this->pluginsService->method('getByMpmPluginId')->willReturn(null);

        $fallback = $this->resolverService->getFallbackSmsGateway();
        $this->assertNull($fallback);
    }

    public function testSmsSenderFallsBackToNormalSmsGatewayWhenWhatsappDeliveryFails(): void {
        $whatsappMock = $this->createMock(WhatsappGateway::class);
        $whatsappMock->expects($this->once())
            ->method('sendSms')
            ->willThrowException(new MessageNotSentException('Customer is offline or undeliverable (#131026)'));
        $this->app->instance(WhatsappGateway::class, $whatsappMock);

        $africasTalkingMock = $this->createMock(AfricasTalkingGateway::class);
        $africasTalkingMock->expects($this->once())
            ->method('sendSms')
            ->with('STS Token: 1234-5678', '+255712345678', $this->isInstanceOf(Sms::class));
        $this->app->instance(AfricasTalkingGateway::class, $africasTalkingMock);

        $smsServiceMock = $this->createMock(SmsService::class);
        $smsServiceMock->expects($this->once())
            ->method('markSent')
            ->with($this->isInstanceOf(Sms::class), MpmPlugin::AFRICAS_TALKING);
        $this->app->instance(SmsService::class, $smsServiceMock);

        $resolverMock = $this->createMock(SmsGatewayResolverService::class);
        $resolverMock->expects($this->once())
            ->method('determineGateway')
            ->with('+255712345678')
            ->willReturn([SmsGatewayResolverService::WHATSAPP_GATEWAY, null]);

        $resolverMock->expects($this->once())
            ->method('getFallbackSmsGateway')
            ->willReturn(SmsGatewayResolverService::AFRICAS_TALKING_GATEWAY);

        $sms = new Sms([
            'id' => 42,
            'receiver' => '+255712345678',
            'body' => 'STS Token: 1234-5678',
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
        ]);

        $resolverMock->expects($this->exactly(2))
            ->method('resolveGatewayAndArgs')
            ->willReturnCallback(function (string $gw, Sms $s, array $params) use ($whatsappMock, $africasTalkingMock) {
                if ($gw === SmsGatewayResolverService::WHATSAPP_GATEWAY) {
                    return [
                        'gateway' => $whatsappMock,
                        'args' => [$params['body'], $params['receiver'], $s],
                        'gatewayId' => MpmPlugin::WHATSAPP_BUSINESS_API,
                    ];
                }

                return [
                    'gateway' => $africasTalkingMock,
                    'args' => [$params['body'], $params['receiver'], $s],
                    'gatewayId' => MpmPlugin::AFRICAS_TALKING,
                ];
            });

        $this->app->instance(SmsGatewayResolverService::class, $resolverMock);

        // Concrete implementation of abstract SmsSender
        $sender = new class([], null, '') extends SmsSender {
            public function __construct(mixed $data, mixed $smsBodyService, string $parserSubPath) {
                parent::__construct($data, $smsBodyService, $parserSubPath);
                $this->body = 'STS Token: 1234-5678';
                $this->receiver = '+255712345678';
            }
        };

        $sender->setSms($sms);
        $sender->sendSms();

        $this->assertTrue(true);
    }
}
