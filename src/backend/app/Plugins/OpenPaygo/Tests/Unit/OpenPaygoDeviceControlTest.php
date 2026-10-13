<?php

declare(strict_types=1);

namespace App\Plugins\OpenPaygo\Tests\Unit;

use App\Events\NewLogEvent;
use App\Models\Device;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\OpenPaygo\Models\OpenPaygoDeviceConfiguration;
use App\Plugins\OpenPaygo\Services\OpenPaygoDeviceConfigurationService;
use App\Services\AppliancePaymentService;
use App\Services\CashTransactionService;
use App\Services\DeviceControlService;
use Database\Factories\ApplianceFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\SolarHomeSystemFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class OpenPaygoDeviceControlTest extends TestCase {
    use MockeryPHPUnitIntegration;
    use RefreshMultipleDatabases;

    public function testResetCreatesTokenThroughTransactionAwareIssuancePath(): void {
        $manufacturer = ManufacturerFactory::new()->create([
            'type' => 'shs',
            'api_name' => 'OpenPaygoApi',
        ]);
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => ApplianceTypeFactory::new()->create()->id,
        ]);
        $solarHomeSystem = SolarHomeSystemFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
        ]);
        $device = DeviceFactory::new()->create([
            'person_id' => null,
            'device_id' => $solarHomeSystem->id,
            'device_type' => 'solar_home_system',
        ]);
        $configuration = (new OpenPaygoDeviceConfigurationService())->saveForDevice(
            $device,
            '0123456789abcdef0123456789abcdef',
            123,
            4,
        );
        $transaction = Transaction::query()->create([
            'original_transaction_id' => 1,
            'original_transaction_type' => 'App\\Models\\Transaction\\CashTransaction',
            'amount' => 0,
            'type' => Transaction::TYPE_AD_HOC,
            'sender' => '',
            'message' => $device->device_serial,
        ]);
        $cashTransactionService = Mockery::mock(CashTransactionService::class);
        $cashTransactionService->shouldReceive('createTransaction')->once()->andReturn($transaction);
        $appliancePaymentService = Mockery::mock(AppliancePaymentService::class);
        config()->set('services.openpaygo_generator.url', 'https://generator.example');
        config()->set('services.openpaygo_generator.api_key', 'test-key');
        Http::fake([
            'https://generator.example/generate' => Http::response([
                'token' => 'RESET-123',
                'nextCounter' => 9,
            ]),
        ]);
        Event::fake([NewLogEvent::class]);

        $token = (new DeviceControlService($cashTransactionService, $appliancePaymentService))
            ->generateResetToken($device, 1);

        $this->assertSame(Token::TYPE_RESET, $token->token_type);
        $this->assertSame($transaction->id, $token->transaction_id);
        $this->assertSame(9, $configuration->fresh()->next_counter);
        Event::assertDispatched(
            NewLogEvent::class,
            fn (NewLogEvent $event): bool => !str_contains($event->logData['action'], $token->token),
        );
        Http::assertSentCount(1);
    }
}
