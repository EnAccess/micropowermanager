<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Plugins\WhatsappBusinessApi\Services\WhatsappSelfService;
use App\Plugins\WhatsappBusinessApi\WhatsappGateway;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class WhatsappSelfServiceTest extends TestCase {
    private WhatsappSelfService $selfService;
    private WhatsappGateway&MockObject $gateway;

    protected function setUp(): void {
        parent::setUp();

        $this->gateway = $this->createMock(WhatsappGateway::class);
        $this->selfService = new WhatsappSelfService($this->gateway);
    }

    public function testResolvesInteractiveActionIds(): void {
        $this->assertSame(
            WhatsappSelfService::ACTION_LATEST_TOKEN,
            $this->selfService->resolveAction('', WhatsappSelfService::ACTION_LATEST_TOKEN)
        );

        $this->assertSame(
            WhatsappSelfService::ACTION_NOT_RECEIVED_TOKEN,
            $this->selfService->resolveAction('', WhatsappSelfService::ACTION_NOT_RECEIVED_TOKEN)
        );

        $this->assertSame(
            WhatsappSelfService::ACTION_LOW_BALANCE,
            $this->selfService->resolveAction('', WhatsappSelfService::ACTION_LOW_BALANCE)
        );

        $this->assertSame(
            WhatsappSelfService::ACTION_METER_INFO,
            $this->selfService->resolveAction('', WhatsappSelfService::ACTION_METER_INFO)
        );
    }

    public function testResolvesTextCommandsAndKeywords(): void {
        // Latest token variations
        $this->assertSame(
            WhatsappSelfService::ACTION_LATEST_TOKEN,
            $this->selfService->resolveAction('to get latest token')
        );
        $this->assertSame(
            WhatsappSelfService::ACTION_LATEST_TOKEN,
            $this->selfService->resolveAction('Get Latest Token')
        );
        $this->assertSame(
            WhatsappSelfService::ACTION_LATEST_TOKEN,
            $this->selfService->resolveAction('1')
        );

        // Did not receive token variations (including spelling 'recieve')
        $this->assertSame(
            WhatsappSelfService::ACTION_NOT_RECEIVED_TOKEN,
            $this->selfService->resolveAction('did not recieve token')
        );
        $this->assertSame(
            WhatsappSelfService::ACTION_NOT_RECEIVED_TOKEN,
            $this->selfService->resolveAction('Did not receive token')
        );
        $this->assertSame(
            WhatsappSelfService::ACTION_NOT_RECEIVED_TOKEN,
            $this->selfService->resolveAction('missing token')
        );
        $this->assertSame(
            WhatsappSelfService::ACTION_NOT_RECEIVED_TOKEN,
            $this->selfService->resolveAction('2')
        );

        // Status for low balance variations
        $this->assertSame(
            WhatsappSelfService::ACTION_LOW_BALANCE,
            $this->selfService->resolveAction('status for low balance')
        );
        $this->assertSame(
            WhatsappSelfService::ACTION_LOW_BALANCE,
            $this->selfService->resolveAction('Status low balance')
        );
        $this->assertSame(
            WhatsappSelfService::ACTION_LOW_BALANCE,
            $this->selfService->resolveAction('balance')
        );
        $this->assertSame(
            WhatsappSelfService::ACTION_LOW_BALANCE,
            $this->selfService->resolveAction('3')
        );

        // Meter info variations
        $this->assertSame(
            WhatsappSelfService::ACTION_METER_INFO,
            $this->selfService->resolveAction('meter info')
        );
        $this->assertSame(
            WhatsappSelfService::ACTION_METER_INFO,
            $this->selfService->resolveAction('Meter Information')
        );
        $this->assertSame(
            WhatsappSelfService::ACTION_METER_INFO,
            $this->selfService->resolveAction('4')
        );
    }

    public function testReturnsNullForMeaninglessOrUnspecifiedWords(): void {
        $this->assertNull($this->selfService->resolveAction('hello'));
        $this->assertNull($this->selfService->resolveAction('good morning'));
        $this->assertNull($this->selfService->resolveAction('help please'));
        $this->assertNull($this->selfService->resolveAction('what is my payment status?'));
        $this->assertNull($this->selfService->resolveAction('asdfgh random gibberish'));
        $this->assertNull($this->selfService->resolveAction('???'));
    }
}
