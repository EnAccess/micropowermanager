<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Plugins\ViberMessaging\Services\ViberContactService;
use App\Plugins\WhatsappBusinessApi\WhatsappGateway;
use App\Services\MainSettingsService;
use App\Services\PluginsService;
use App\Services\SmsGatewayResolverService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class SmsGatewayResolverWhatsappTest extends TestCase {
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

    public function testIncludesWhatsappGatewayWhenPluginIsActive(): void {
        $whatsappPlugin = new Plugins([
            'mpm_plugin_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'status' => Plugins::ACTIVE,
        ]);

        $this->pluginsService->method('getByMpmPluginId')
            ->willReturnCallback(function (int $pluginId) use ($whatsappPlugin): ?Plugins {
                if ($pluginId === MpmPlugin::WHATSAPP_BUSINESS_API) {
                    return $whatsappPlugin;
                }

                return null;
            });

        $gateways = $this->resolverService->getAvailableSmsGateways();

        $whatsappGateways = array_filter($gateways, fn (array $g): bool => $g['id'] === MpmPlugin::WHATSAPP_BUSINESS_API);
        $this->assertNotEmpty($whatsappGateways);
        $gateway = array_values($whatsappGateways)[0];
        $this->assertSame('WhatsappGateway', $gateway['name']);
        $this->assertSame('WhatsApp Business API', $gateway['label']);
        $this->assertTrue($gateway['is_active']);
    }

    public function testExcludesWhatsappGatewayWhenPluginIsDisabled(): void {
        $this->pluginsService->method('getByMpmPluginId')->willReturn(null);

        $gateways = $this->resolverService->getAvailableSmsGateways();

        $whatsappGateways = array_filter($gateways, fn (array $g): bool => $g['id'] === MpmPlugin::WHATSAPP_BUSINESS_API);
        $this->assertEmpty($whatsappGateways);
    }

    public function testDeterminesWhatsappGatewayFromMainSettings(): void {
        $settings = new \App\Models\MainSettings([
            'sms_gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
        ]);
        $this->mainSettingsService->method('getAll')->willReturn(new \Illuminate\Database\Eloquent\Collection([$settings]));

        [$gateway, $viberId] = $this->resolverService->determineGateway('+255712345678');
        $this->assertSame(SmsGatewayResolverService::WHATSAPP_GATEWAY, $gateway);
        $this->assertNull($viberId);
    }

    public function testResolvesWhatsappGatewayInstanceAndArguments(): void {
        $whatsappMock = $this->createMock(WhatsappGateway::class);
        $this->app->instance(WhatsappGateway::class, $whatsappMock);

        $sms = new Sms([
            'receiver' => '+255712345678',
            'body' => 'Default SMS Body',
        ]);

        $resolved = $this->resolverService->resolveGatewayAndArgs(
            SmsGatewayResolverService::WHATSAPP_GATEWAY,
            $sms,
            ['body' => 'Custom Body', 'receiver' => '+255700000000'],
        );

        $this->assertSame($whatsappMock, $resolved['gateway']);
        $this->assertSame(MpmPlugin::WHATSAPP_BUSINESS_API, $resolved['gatewayId']);
        $this->assertSame(['Custom Body', '+255700000000', $sms], $resolved['args']);
    }
}
