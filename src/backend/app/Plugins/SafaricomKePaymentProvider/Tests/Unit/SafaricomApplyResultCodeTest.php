<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomKePaymentProvider\Tests\Unit;

use App\Jobs\ProcessPayment;
use App\Plugins\SafaricomKePaymentProvider\Models\SafaricomTransaction;
use App\Plugins\SafaricomKePaymentProvider\Services\SafaricomTransactionService;
use Illuminate\Support\Facades\Bus;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class SafaricomApplyResultCodeTest extends TestCase {
    use RefreshMultipleDatabases;

    private const int COMPANY_ID = 42;
    private const int RESULT_SUCCESS = 0;
    private const int RESULT_INSUFFICIENT_FUNDS = 1;
    private const int RESULT_USER_CANCELLED = 1032;

    private function makeService(): SafaricomTransactionService {
        /** @var SafaricomTransactionService $service */
        $service = $this->app->make(SafaricomTransactionService::class);

        return $service;
    }

    private function persistTransaction(): SafaricomTransaction {
        /** @var SafaricomTransaction $safaricomTransaction */
        $safaricomTransaction = SafaricomTransaction::query()->create([
            'amount' => 100.0,
            'currency' => 'KES',
            'order_id' => 'order_'.uniqid(),
            'reference_id' => 'ref_'.uniqid(),
            'status' => SafaricomTransaction::STATUS_REQUESTED,
            'customer_id' => 1,
            'phone_number' => '+254700000000',
            'checkout_request_id' => 'ws_CO_'.uniqid(),
        ]);
        $safaricomTransaction->transaction()->create([
            'amount' => 100.0,
            'sender' => '+254700000000',
            'message' => 'meter-1',
            'type' => 'energy',
        ]);
        $safaricomTransaction->refresh();

        return $safaricomTransaction;
    }

    /**
     * @return array<string, mixed>
     */
    private function callbackPayload(int $resultCode, float $amount = 100.0): array {
        return [
            'source' => 'webhook',
            'result_code' => $resultCode,
            'result_desc' => $resultCode === self::RESULT_SUCCESS ? 'The service request is processed successfully.' : 'Failed',
            'mpesa_receipt' => $resultCode === self::RESULT_SUCCESS ? 'QGR11ABCDE' : null,
            'amount' => $amount,
        ];
    }

    public function testResultCodeZeroCreditsPaymentAndDispatchesProcessing(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyResultCode(
            $transaction,
            self::RESULT_SUCCESS,
            $this->callbackPayload(self::RESULT_SUCCESS),
            self::COMPANY_ID
        );

        $this->assertSame(SafaricomTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
        $this->assertSame('QGR11ABCDE', $transaction->fresh()->mpesa_receipt_number);
        $this->assertSame('QGR11ABCDE', $transaction->fresh()->external_transaction_id);
        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
    }

    public function testReplayedSuccessCallbackCreditsOnlyOnce(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService();
        $payload = $this->callbackPayload(self::RESULT_SUCCESS);

        $service->applyResultCode($transaction, self::RESULT_SUCCESS, $payload, self::COMPANY_ID);
        $service->applyResultCode($transaction, self::RESULT_SUCCESS, $payload, self::COMPANY_ID);
        $service->applyResultCode($transaction->fresh(), self::RESULT_SUCCESS, $payload, self::COMPANY_ID);

        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
        $this->assertSame(SafaricomTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
    }

    /**
     * The STK callback and the queryStatus poll race each other, so a failure result arriving
     * after the payment concluded must leave the success alone.
     */
    public function testLateFailureDoesNotDowngradeASettledPayment(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService();

        $service->applyResultCode(
            $transaction,
            self::RESULT_SUCCESS,
            $this->callbackPayload(self::RESULT_SUCCESS),
            self::COMPANY_ID
        );
        $service->applyResultCode(
            $transaction->fresh(),
            self::RESULT_INSUFFICIENT_FUNDS,
            $this->callbackPayload(self::RESULT_INSUFFICIENT_FUNDS),
            self::COMPANY_ID
        );

        $this->assertSame(SafaricomTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
    }

    /**
     * An unverifiable notification is not a failed payment, so the status is left as it was and a
     * conflict is recorded for an operator to pick up.
     */
    public function testSuccessWithMismatchedPaidAmountRefusesCreditAndRecordsAConflict(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyResultCode(
            $transaction,
            self::RESULT_SUCCESS,
            $this->callbackPayload(self::RESULT_SUCCESS, 5.0),
            self::COMPANY_ID
        );

        $this->assertSame(SafaricomTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);

        $conflicts = $transaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame('M-PESA amount does not match the stored transaction.', $conflicts->first()->state);
    }

    public function testMismatchStillPersistsTheMpesaReceiptAndResultCode(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyResultCode(
            $transaction,
            self::RESULT_SUCCESS,
            $this->callbackPayload(self::RESULT_SUCCESS, 5.0),
            self::COMPANY_ID
        );

        $stored = $transaction->fresh();
        $this->assertSame('QGR11ABCDE', $stored->mpesa_receipt_number);
        $this->assertSame('QGR11ABCDE', $stored->external_transaction_id);
        $this->assertSame(self::RESULT_SUCCESS, ($stored->response_data ?? [])['final_result_code']);
    }

    /**
     * Daraja redelivers callbacks and the STK page polls, so the same mismatch is seen repeatedly.
     */
    public function testRepeatedMismatchRecordsASingleConflict(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService();
        $payload = $this->callbackPayload(self::RESULT_SUCCESS, 5.0);

        $service->applyResultCode($transaction, self::RESULT_SUCCESS, $payload, self::COMPANY_ID);
        $service->applyResultCode($transaction->fresh(), self::RESULT_SUCCESS, $payload, self::COMPANY_ID);
        $service->applyResultCode($transaction->fresh(), self::RESULT_SUCCESS, $payload, self::COMPANY_ID);

        $this->assertCount(1, $transaction->conflicts()->get());
        Bus::assertNotDispatched(ProcessPayment::class);
    }

    /**
     * A conflicted transaction is terminal: the poll must stop rather than run to its attempt cap
     * and report a timeout.
     */
    public function testConflictedTransactionIsReportedAsResolved(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyResultCode(
            $transaction,
            self::RESULT_SUCCESS,
            $this->callbackPayload(self::RESULT_SUCCESS, 5.0),
            self::COMPANY_ID
        );

        $snapshot = $this->makeService()->queryStatus($transaction->fresh(), self::COMPANY_ID);
        $this->assertTrue($snapshot['resolved']);
    }

    public function testUserCancelledMarksAbandoned(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyResultCode(
            $transaction,
            self::RESULT_USER_CANCELLED,
            $this->callbackPayload(self::RESULT_USER_CANCELLED),
            self::COMPANY_ID
        );

        $this->assertSame(SafaricomTransaction::STATUS_ABANDONED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);
    }

    public function testNonZeroResultCodeMarksFailed(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyResultCode(
            $transaction,
            self::RESULT_INSUFFICIENT_FUNDS,
            $this->callbackPayload(self::RESULT_INSUFFICIENT_FUNDS),
            self::COMPANY_ID
        );

        $this->assertSame(SafaricomTransaction::STATUS_FAILED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);
    }

    public function testResultCodeIsRecordedOnTheTransaction(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyResultCode(
            $transaction,
            self::RESULT_SUCCESS,
            $this->callbackPayload(self::RESULT_SUCCESS),
            self::COMPANY_ID
        );

        $responseData = $transaction->fresh()->response_data ?? [];
        $this->assertSame(self::RESULT_SUCCESS, $responseData['final_result_code']);
    }
}
