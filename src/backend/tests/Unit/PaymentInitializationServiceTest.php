<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\MpmPlugin;
use App\Models\Transaction\Transaction;
use App\Plugins\PaystackPaymentProvider\Services\PaystackTransactionService;
use App\Services\CashTransactionService;
use App\Services\MpmPluginService;
use App\Services\PaymentInitializationService;
use App\Services\PluginsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentInitializationServiceTest extends TestCase {
    private PaymentInitializationService $service;

    /** @var CashTransactionService&MockObject */
    private MockObject $cashService;

    /** @var PaystackTransactionService&MockObject */
    private MockObject $paystackService;

    protected function setUp(): void {
        parent::setUp();

        $this->cashService = $this->createMock(CashTransactionService::class);
        $this->paystackService = $this->createMock(PaystackTransactionService::class);
        $pluginsService = $this->createMock(PluginsService::class);
        $mpmPluginService = $this->createMock(MpmPluginService::class);

        $this->service = new PaymentInitializationService(
            $this->cashService,
            $this->paystackService,
            $pluginsService,
            $mpmPluginService,
        );
    }

    public function testThrowsForUnknownProviderId(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment provider ID: 999');

        $this->service->initialize(
            providerId: 999,
            amount: 100.0,
            sender: '+2340000',
            message: 'DEVICE-001',
            type: 'deferred_payment',
            customerId: 1,
            creatorId: 1,
        );
    }

    public function testDelegatesToCashTransactionServiceForProviderZero(): void {
        $transaction = new Transaction();

        $this->cashService
            ->expects($this->once())
            ->method('createTransaction')
            ->with(1, 100.0, '+2340000', '42', 'deferred_payment')
            ->willReturn($transaction);

        $result = $this->service->initialize(
            providerId: 0,
            amount: 100.0,
            sender: '+2340000',
            message: '42',
            type: 'deferred_payment',
            customerId: 5,
            creatorId: 1,
        );

        $this->assertSame($transaction, $result['transaction']);
        $this->assertSame([], $result['provider_data']);
    }

    public function testDelegatesToPaystackServiceForPaystackProvider(): void {
        $transaction = new Transaction();

        $this->paystackService
            ->expects($this->once())
            ->method('initializePayment')
            ->with(100.0, '+2340000', '42', 'deferred_payment', 5, null)
            ->willReturn([
                'transaction' => $transaction,
                'provider_data' => [
                    'redirect_url' => 'https://paystack.com/pay/abc',
                    'reference' => 'ref_abc',
                ],
            ]);

        $result = $this->service->initialize(
            providerId: MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            amount: 100.0,
            sender: '+2340000',
            message: '42',
            type: 'deferred_payment',
            customerId: 5,
            creatorId: 1,
        );

        $this->assertSame($transaction, $result['transaction']);
        $this->assertSame('https://paystack.com/pay/abc', $result['provider_data']['redirect_url']);
    }

    public function testDoesNotCallPaystackServiceForCashProvider(): void {
        $transaction = new Transaction();

        $this->cashService->method('createTransaction')->willReturn($transaction);
        $this->paystackService->expects($this->never())->method('initializePayment');

        $this->service->initialize(
            providerId: 0,
            amount: 50.0,
            sender: '-',
            message: '1',
            type: 'deferred_payment',
            customerId: 1,
            creatorId: 1,
        );
    }

    public function testDoesNotCallCashServiceForPaystackProvider(): void {
        $transaction = new Transaction();

        $this->paystackService->method('initializePayment')->willReturn([
            'transaction' => $transaction,
            'provider_data' => ['redirect_url' => 'https://paystack.com/pay/x', 'reference' => 'ref_x'],
        ]);
        $this->cashService->expects($this->never())->method('createTransaction');

        $this->service->initialize(
            providerId: MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            amount: 50.0,
            sender: '-',
            message: '1',
            type: 'deferred_payment',
            customerId: 1,
            creatorId: 1,
        );
    }

    public function testPassesSerialIdToPaystackServiceWhenProvided(): void {
        $transaction = new Transaction();

        $this->paystackService
            ->expects($this->once())
            ->method('initializePayment')
            ->with(200.0, '+2340000', 'SERIAL-001', 'deferred_payment', 5, 'SERIAL-001')
            ->willReturn([
                'transaction' => $transaction,
                'provider_data' => ['redirect_url' => 'https://paystack.com/pay/y', 'reference' => 'ref_y'],
            ]);

        $this->service->initialize(
            providerId: MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            amount: 200.0,
            sender: '+2340000',
            message: 'SERIAL-001',
            type: 'deferred_payment',
            customerId: 5,
            creatorId: 1,
            serialId: 'SERIAL-001',
        );
    }
}
