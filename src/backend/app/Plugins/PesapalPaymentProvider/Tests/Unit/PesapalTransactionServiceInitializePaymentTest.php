<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Tests\Unit;

use App\Models\Transaction\Transaction;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApiService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class PesapalTransactionServiceInitializePaymentTest extends TestCase {
    use RefreshMultipleDatabases;

    /**
     * @param array<string, mixed> $apiResponse
     */
    private function makeServiceWithMockedApi(array $apiResponse): PesapalTransactionService {
        $apiService = $this->createMock(PesapalApiService::class);
        $apiService->method('submitOrder')->willReturn($apiResponse);

        $this->app->instance(PesapalApiService::class, $apiService);

        /** @var PesapalTransactionService $service */
        $service = $this->app->make(PesapalTransactionService::class);

        return $service;
    }

    public function testCreatesPesapalTransactionAndTransactionRecords(): void {
        $service = $this->makeServiceWithMockedApi([
            'error' => null,
            'redirect_url' => 'https://cybqa.pesapal.com/pay/test',
            'order_tracking_id' => 'ot_test_123',
            'merchant_reference' => 'mr_test_123',
        ]);

        $result = $service->initializePayment(
            amount: 200.0,
            sender: '+254700000000',
            message: '42',
            type: 'deferred_payment',
            customerId: 1,
        );

        $this->assertInstanceOf(Transaction::class, $result['transaction']);
        $this->assertSame('42', $result['transaction']->message);
        $this->assertSame('deferred_payment', $result['transaction']->type);
        $this->assertSame('https://cybqa.pesapal.com/pay/test', $result['provider_data']['redirect_url']);
        $this->assertSame('ot_test_123', $result['provider_data']['order_tracking_id']);
        $this->assertSame('mr_test_123', $result['provider_data']['merchant_reference']);

        $pesapalTxn = PesapalTransaction::query()->where('customer_id', 1)->where('amount', 200.0)->first();
        $this->assertNotNull($pesapalTxn);

        $transaction = Transaction::query()->where('message', '42')->where('type', 'deferred_payment')->first();
        $this->assertNotNull($transaction);
    }

    public function testSetsSerialIdOnPesapalTransactionWhenProvided(): void {
        $service = $this->makeServiceWithMockedApi([
            'error' => null,
            'redirect_url' => 'https://cybqa.pesapal.com/pay/serial',
            'order_tracking_id' => 'ot_serial',
            'merchant_reference' => 'mr_serial',
        ]);

        $service->initializePayment(
            amount: 150.0,
            sender: '+254700000001',
            message: 'SERIAL-XYZ',
            type: 'deferred_payment',
            customerId: 2,
            serialId: 'SERIAL-XYZ',
        );

        $pesapalTxn = PesapalTransaction::query()->where('serial_id', 'SERIAL-XYZ')->first();
        $this->assertNotNull($pesapalTxn);

        $transaction = Transaction::query()->where('message', 'SERIAL-XYZ')->first();
        $this->assertNotNull($transaction);
    }

    public function testThrowsWhenPesapalApiReturnsError(): void {
        $service = $this->makeServiceWithMockedApi([
            'error' => 'API error: invalid consumer key',
            'redirect_url' => null,
            'order_tracking_id' => null,
            'merchant_reference' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pesapal initialization failed: API error: invalid consumer key');

        $service->initializePayment(
            amount: 100.0,
            sender: '-',
            message: '1',
            type: 'deferred_payment',
            customerId: 1,
        );
    }
}
