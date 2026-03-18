<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Transaction\Transaction;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\PaystackPaymentProvider\Modules\Api\PaystackApiService;
use App\Plugins\PaystackPaymentProvider\Services\PaystackTransactionService;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class PaystackTransactionServiceInitializePaymentTest extends TestCase {
    use RefreshMultipleDatabases;

    private function makeServiceWithMockedApi(array $apiResponse): PaystackTransactionService {
        $apiService = $this->createMock(PaystackApiService::class);
        $apiService->method('initializeTransaction')->willReturn($apiResponse);

        $this->app->instance(PaystackApiService::class, $apiService);

        /** @var PaystackTransactionService $service */
        $service = $this->app->make(PaystackTransactionService::class);

        return $service;
    }

    public function testCreatesPaystackTransactionAndTransactionRecords(): void {
        $service = $this->makeServiceWithMockedApi([
            'error' => null,
            'redirectionUrl' => 'https://paystack.com/pay/test',
            'reference' => 'ref_test_123',
        ]);

        $result = $service->initializePayment(
            amount: 200.0,
            sender: '+2340000',
            message: '42',
            type: 'deferred_payment',
            customerId: 1,
        );

        $this->assertInstanceOf(Transaction::class, $result['transaction']);
        $this->assertSame('42', $result['transaction']->message);
        $this->assertSame('deferred_payment', $result['transaction']->type);
        $this->assertSame('https://paystack.com/pay/test', $result['provider_data']['redirect_url']);
        $this->assertSame('ref_test_123', $result['provider_data']['reference']);

        $paystackTxn = PaystackTransaction::query()->where('customer_id', 1)->where('amount', 200.0)->first();
        $this->assertNotNull($paystackTxn);

        $transaction = Transaction::query()->where('message', '42')->where('type', 'deferred_payment')->first();
        $this->assertNotNull($transaction);
    }

    public function testSetsSerialIdOnPaystackTransactionWhenProvided(): void {
        $service = $this->makeServiceWithMockedApi([
            'error' => null,
            'redirectionUrl' => 'https://paystack.com/pay/serial',
            'reference' => 'ref_serial',
        ]);

        $service->initializePayment(
            amount: 150.0,
            sender: '+2340001',
            message: 'SERIAL-XYZ',
            type: 'deferred_payment',
            customerId: 2,
            serialId: 'SERIAL-XYZ',
        );

        $paystackTxn = PaystackTransaction::query()->where('serial_id', 'SERIAL-XYZ')->first();
        $this->assertNotNull($paystackTxn);

        $transaction = Transaction::query()->where('message', 'SERIAL-XYZ')->first();
        $this->assertNotNull($transaction);
    }

    public function testThrowsWhenPaystackApiReturnsError(): void {
        $service = $this->makeServiceWithMockedApi([
            'error' => 'API error: invalid key',
            'redirectionUrl' => null,
            'reference' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Paystack initialization failed: API error: invalid key');

        $service->initializePayment(
            amount: 100.0,
            sender: '-',
            message: '1',
            type: 'deferred_payment',
            customerId: 1,
        );
    }
}
