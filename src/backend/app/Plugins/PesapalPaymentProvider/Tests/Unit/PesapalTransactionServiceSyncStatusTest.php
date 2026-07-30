<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Tests\Unit;

use App\Jobs\ProcessPayment;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApiService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use Illuminate\Support\Facades\Bus;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class PesapalTransactionServiceSyncStatusTest extends TestCase {
    use RefreshMultipleDatabases;

    /**
     * @param array<string, mixed> $statusResponse
     */
    private function makeService(array $statusResponse): PesapalTransactionService {
        $apiService = $this->createMock(PesapalApiService::class);
        $apiService->method('getTransactionStatus')->willReturn($statusResponse);
        $this->app->instance(PesapalApiService::class, $apiService);

        /** @var PesapalTransactionService $service */
        $service = $this->app->make(PesapalTransactionService::class);

        return $service;
    }

    private function persistTransaction(): PesapalTransaction {
        /** @var PesapalTransaction $pesapalTxn */
        $pesapalTxn = PesapalTransaction::query()->create([
            'amount' => 100.0,
            'currency' => 'KES',
            'order_id' => 'order_'.uniqid(),
            'reference_id' => 'ref_'.uniqid(),
            'status' => PesapalTransaction::STATUS_REQUESTED,
            'customer_id' => 1,
            'order_tracking_id' => 'ot_'.uniqid(),
            'merchant_reference' => 'mr_abc',
        ]);
        $pesapalTxn->transaction()->create([
            'amount' => 100.0,
            'sender' => '+254700000000',
            'message' => 'meter-1',
            'type' => 'energy',
        ]);
        $pesapalTxn->refresh();

        return $pesapalTxn;
    }

    public function testStatusCodeOneMarksSuccessAndDispatchesProcessing(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService($this->statusResult(1, 'COMPLETED', 'CONF_99'));

        $result = $service->syncStatusFromApi($transaction, 42);

        $this->assertNull($result['error']);
        $this->assertSame('CONF_99', $transaction->fresh()->external_transaction_id);
        $this->assertSame(PesapalTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
    }

    public function testCompletedStatusIsIdempotentWhenReplayed(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService($this->statusResult(1, 'COMPLETED', 'CONF_99'));

        $service->syncStatusFromApi($transaction, 42);
        $service->syncStatusFromApi($transaction, 42);
        $service->syncStatusFromApi($transaction->fresh(), 42);

        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
        $this->assertSame(PesapalTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
    }

    public function testCompletedStatusWithMerchantReferenceMismatchRefusesCredit(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $response = $this->statusResult(1, 'COMPLETED', 'CONF_99');
        $response['merchant_reference'] = 'another-reference';
        $service = $this->makeService($response);

        $result = $service->syncStatusFromApi($transaction, 42);

        $this->assertSame(
            'PesaPal merchant reference does not match the stored transaction.',
            $result['error']
        );
        $this->assertSame(PesapalTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        $this->assertNull($transaction->fresh()->external_transaction_id);
        Bus::assertNotDispatched(ProcessPayment::class);
    }

    public function testCompletedStatusWithCurrencyMismatchRefusesCredit(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $response = $this->statusResult(1, 'COMPLETED', 'CONF_99');
        $response['currency'] = 'UGX';
        $service = $this->makeService($response);

        $result = $service->syncStatusFromApi($transaction, 42);

        $this->assertSame('PesaPal currency does not match the stored transaction.', $result['error']);
        $this->assertSame(PesapalTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);
    }

    public function testCompletedStatusWithAmountMismatchRefusesCredit(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $response = $this->statusResult(1, 'COMPLETED', 'CONF_99');
        $response['amount'] = 99.99;
        $service = $this->makeService($response);

        $result = $service->syncStatusFromApi($transaction, 42);

        $this->assertSame('PesaPal amount does not match the stored transaction.', $result['error']);
        $this->assertSame(PesapalTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);
    }

    public function testStatusCodeTwoMarksFailed(): void {
        $transaction = $this->persistTransaction();
        $service = $this->makeService($this->statusResult(2, 'FAILED'));

        $service->syncStatusFromApi($transaction, 42);

        $this->assertSame(PesapalTransaction::STATUS_FAILED, $transaction->fresh()->status);
    }

    public function testStatusCodeThreeReversedMarksFailed(): void {
        $transaction = $this->persistTransaction();
        $service = $this->makeService($this->statusResult(3, 'REVERSED'));

        $service->syncStatusFromApi($transaction, 42);

        $this->assertSame(PesapalTransaction::STATUS_FAILED, $transaction->fresh()->status);
    }

    public function testStatusCodeZeroInvalidMarksAbandoned(): void {
        $transaction = $this->persistTransaction();
        $service = $this->makeService($this->statusResult(0, 'INVALID'));

        $service->syncStatusFromApi($transaction, 42);

        $this->assertSame(PesapalTransaction::STATUS_ABANDONED, $transaction->fresh()->status);
    }

    public function testApiErrorLeavesTransactionUntouched(): void {
        $transaction = $this->persistTransaction();
        $service = $this->makeService([
            'status_code' => null,
            'status_description' => '',
            'amount' => 0.0,
            'currency' => '',
            'payment_method' => '',
            'confirmation_code' => '',
            'merchant_reference' => '',
            'error' => 'network blip',
        ]);

        $result = $service->syncStatusFromApi($transaction, 42);

        $this->assertSame('network blip', $result['error']);
        $this->assertSame(PesapalTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        $this->assertNull($transaction->fresh()->external_transaction_id);
    }

    /**
     * @return array{status_code: int, status_description: string, amount: float, currency: string, payment_method: string, confirmation_code: string, merchant_reference: string, error: null}
     */
    private function statusResult(int $code, string $description, string $confirmationCode = ''): array {
        return [
            'status_code' => $code,
            'status_description' => $description,
            'amount' => 100.0,
            'currency' => 'KES',
            'payment_method' => 'CARD',
            'confirmation_code' => $confirmationCode,
            'merchant_reference' => 'mr_abc',
            'error' => null,
        ];
    }
}
