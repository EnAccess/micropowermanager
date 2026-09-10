<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PaymentInitiationProvider;
use App\Exceptions\PaymentProviderNotEnabledException;
use App\Models\MpmPlugin;
use App\Models\Transaction\Transaction;
use App\Plugins\PaystackPaymentProvider\Services\PaystackTransactionService;
use App\Services\CashTransactionService;
use App\Services\MpmPluginService;
use App\Services\PaymentInitiationService;
use App\Services\PluginsService;
use App\Services\ThirdPartyTransactionService;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentInitiationServiceTest extends TestCase {
    private PaymentInitiationService $service;

    /** @var Container&MockObject */
    private MockObject $container;

    /** @var CashTransactionService&MockObject */
    private MockObject $cashService;

    /** @var PaystackTransactionService&MockObject */
    private MockObject $paystackService;

    /** @var ThirdPartyTransactionService&MockObject */
    private MockObject $thirdPartyService;

    /** @var PluginsService&MockObject */
    private MockObject $pluginsService;

    protected function setUp(): void {
        parent::setUp();

        $this->cashService = $this->createMock(CashTransactionService::class);
        $this->paystackService = $this->createMock(PaystackTransactionService::class);
        $this->thirdPartyService = $this->createMock(ThirdPartyTransactionService::class);
        $this->pluginsService = $this->createMock(PluginsService::class);
        $mpmPluginService = $this->createMock(MpmPluginService::class);

        $this->container = $this->createMock(Container::class);
        $this->container->method('make')->willReturnMap([
            [CashTransactionService::class, [], $this->cashService],
            [PaystackTransactionService::class, [], $this->paystackService],
            [ThirdPartyTransactionService::class, [], $this->thirdPartyService],
        ]);

        $this->service = new PaymentInitiationService(
            $this->pluginsService,
            $mpmPluginService,
            $this->container,
        );
    }

    private function pluginActive(bool $active): void {
        $this->pluginsService->method('isPluginActive')->willReturn($active);
    }

    public function testThrowsForUnknownProviderId(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment provider ID: 999');

        $this->service->initiate(
            providerId: 999,
            amount: 100.0,
            sender: '+2340000',
            message: 'DEVICE-001',
            type: 'deferred_payment',
            customerId: 1,
        );
    }

    public function testRejectsAProviderWhosePluginIsNotActive(): void {
        $this->pluginActive(false);
        $this->paystackService->expects($this->never())->method('initiatePayment');

        $this->expectException(PaymentProviderNotEnabledException::class);

        $this->service->initiate(
            providerId: MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            amount: 100.0,
            sender: '+2340000',
            message: '42',
            type: 'deferred_payment',
            customerId: 5,
        );
    }

    public function testCashNeedsNoActivePlugin(): void {
        $this->pluginActive(false);
        $this->cashService->method('initiatePayment')
            ->willReturn(['transaction' => new Transaction(), 'provider_data' => []]);

        $result = $this->service->initiate(
            providerId: PaymentInitiationProvider::Cash->value,
            amount: 100.0,
            sender: '-',
            message: '42',
            type: 'deferred_payment',
            customerId: 5,
        );

        $this->assertInstanceOf(Transaction::class, $result['transaction']);
    }

    public function testThirdPartyNeedsNoActivePlugin(): void {
        $this->pluginActive(false);
        $this->thirdPartyService
            ->expects($this->once())
            ->method('initiatePayment')
            ->willReturn(['transaction' => new Transaction(), 'provider_data' => []]);

        $result = $this->service->initiate(
            providerId: PaymentInitiationProvider::ThirdParty->value,
            amount: 100.0,
            sender: '-',
            message: 'SERIAL-001',
            type: 'deferred_payment',
            customerId: 5,
            serialId: 'SERIAL-001',
        );

        $this->assertInstanceOf(Transaction::class, $result['transaction']);
    }

    public function testDelegatesToCashServiceForProviderZero(): void {
        $this->pluginActive(true);
        $transaction = new Transaction();

        $this->cashService
            ->expects($this->once())
            ->method('initiatePayment')
            ->with(100.0, '+2340000', '42', 'deferred_payment', 5, null)
            ->willReturn(['transaction' => $transaction, 'provider_data' => []]);

        $result = $this->service->initiate(
            providerId: 0,
            amount: 100.0,
            sender: '+2340000',
            message: '42',
            type: 'deferred_payment',
            customerId: 5,
        );

        $this->assertSame($transaction, $result['transaction']);
        $this->assertSame([], $result['provider_data']);
    }

    public function testDelegatesToPaystackServiceForPaystackProvider(): void {
        $this->pluginActive(true);
        $transaction = new Transaction();

        $this->paystackService
            ->expects($this->once())
            ->method('initiatePayment')
            ->with(100.0, '+2340000', '42', 'deferred_payment', 5, null)
            ->willReturn([
                'transaction' => $transaction,
                'provider_data' => [
                    'redirect_url' => 'https://paystack.com/pay/abc',
                    'reference' => 'ref_abc',
                ],
            ]);

        $result = $this->service->initiate(
            providerId: MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            amount: 100.0,
            sender: '+2340000',
            message: '42',
            type: 'deferred_payment',
            customerId: 5,
        );

        $this->assertSame($transaction, $result['transaction']);
        $this->assertSame('https://paystack.com/pay/abc', $result['provider_data']['redirect_url']);
    }

    public function testDoesNotCallPaystackServiceForCashProvider(): void {
        $this->pluginActive(true);
        $transaction = new Transaction();

        $this->cashService->method('initiatePayment')
            ->willReturn(['transaction' => $transaction, 'provider_data' => []]);
        $this->paystackService->expects($this->never())->method('initiatePayment');

        $this->service->initiate(
            providerId: 0,
            amount: 50.0,
            sender: '-',
            message: '1',
            type: 'deferred_payment',
            customerId: 1,
        );
    }

    public function testDoesNotCallCashServiceForPaystackProvider(): void {
        $this->pluginActive(true);
        $transaction = new Transaction();

        $this->paystackService->method('initiatePayment')->willReturn([
            'transaction' => $transaction,
            'provider_data' => ['redirect_url' => 'https://paystack.com/pay/x', 'reference' => 'ref_x'],
        ]);
        $this->cashService->expects($this->never())->method('initiatePayment');

        $this->service->initiate(
            providerId: MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            amount: 50.0,
            sender: '-',
            message: '1',
            type: 'deferred_payment',
            customerId: 1,
        );
    }

    public function testPassesSerialIdToPaystackServiceWhenProvided(): void {
        $this->pluginActive(true);
        $transaction = new Transaction();

        $this->paystackService
            ->expects($this->once())
            ->method('initiatePayment')
            ->with(200.0, '+2340000', 'SERIAL-001', 'deferred_payment', 5, 'SERIAL-001')
            ->willReturn([
                'transaction' => $transaction,
                'provider_data' => ['redirect_url' => 'https://paystack.com/pay/y', 'reference' => 'ref_y'],
            ]);

        $this->service->initiate(
            providerId: MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            amount: 200.0,
            sender: '+2340000',
            message: 'SERIAL-001',
            type: 'deferred_payment',
            customerId: 5,
            serialId: 'SERIAL-001',
        );
    }
}
