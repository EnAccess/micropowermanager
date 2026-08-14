<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Tests\Unit;

use App\Models\Transaction\Transaction;
use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use App\Plugins\FlutterwavePaymentProvider\Modules\Api\FlutterwaveApiService;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveTransactionService;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class FlutterwaveTransactionServiceInitiatePaymentTest extends TestCase {
    use RefreshMultipleDatabases;

    /**
     * @param array<string, mixed> $apiResponse
     */
    private function makeServiceWithMockedApi(array $apiResponse): FlutterwaveTransactionService {
        $apiService = $this->createMock(FlutterwaveApiService::class);
        $apiService->method('initializeTransaction')->willReturn($apiResponse);

        $this->app->instance(FlutterwaveApiService::class, $apiService);

        /** @var FlutterwaveTransactionService $service */
        $service = $this->app->make(FlutterwaveTransactionService::class);

        return $service;
    }

    public function testCreatesFlutterwaveTransactionAndTransactionRecords(): void {
        $service = $this->makeServiceWithMockedApi([
            'error' => null,
            'redirectionUrl' => 'https://checkout.flutterwave.com/pay/test',
            'reference' => 'ref_test_123',
        ]);

        $result = $service->initiatePayment(
            amount: 200.0,
            sender: '+2340000',
            message: '42',
            type: 'deferred_payment',
            customerId: 1,
        );

        $this->assertInstanceOf(Transaction::class, $result['transaction']);
        $this->assertSame('42', $result['transaction']->message);
        $this->assertSame('deferred_payment', $result['transaction']->type);
        $this->assertSame('https://checkout.flutterwave.com/pay/test', $result['provider_data']['redirect_url']);
        $this->assertSame('ref_test_123', $result['provider_data']['reference']);

        $flutterwaveTxn = FlutterwaveTransaction::query()->where('customer_id', 1)->where('amount', 200.0)->first();
        $this->assertNotNull($flutterwaveTxn);

        $transaction = Transaction::query()->where('message', '42')->where('type', 'deferred_payment')->first();
        $this->assertNotNull($transaction);
    }

    public function testSetsSerialIdOnFlutterwaveTransactionWhenProvided(): void {
        $service = $this->makeServiceWithMockedApi([
            'error' => null,
            'redirectionUrl' => 'https://checkout.flutterwave.com/pay/serial',
            'reference' => 'ref_serial',
        ]);

        $service->initiatePayment(
            amount: 150.0,
            sender: '+2340001',
            message: 'SERIAL-XYZ',
            type: 'deferred_payment',
            customerId: 2,
            serialId: 'SERIAL-XYZ',
        );

        $flutterwaveTxn = FlutterwaveTransaction::query()->where('serial_id', 'SERIAL-XYZ')->first();
        $this->assertNotNull($flutterwaveTxn);

        $transaction = Transaction::query()->where('message', 'SERIAL-XYZ')->first();
        $this->assertNotNull($transaction);
    }

    public function testThrowsWhenFlutterwaveApiReturnsError(): void {
        $service = $this->makeServiceWithMockedApi([
            'error' => 'API error: invalid key',
            'redirectionUrl' => null,
            'reference' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Flutterwave initialization failed: API error: invalid key');

        $service->initiatePayment(
            amount: 100.0,
            sender: '-',
            message: '1',
            type: 'deferred_payment',
            customerId: 1,
        );
    }
}
