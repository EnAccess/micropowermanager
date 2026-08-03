<?php

declare(strict_types=1);

namespace App\Plugins\WaveMoneyPaymentProvider\Tests\Unit;

use App\Jobs\ProcessPayment;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;
use Illuminate\Support\Facades\Bus;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WaveMoneyTransactionCallbackTest extends TestCase {
    use RefreshMultipleDatabases;

    private const int COMPANY_ID = 42;

    private function makeService(): WaveMoneyTransactionService {
        /** @var WaveMoneyTransactionService $service */
        $service = $this->app->make(WaveMoneyTransactionService::class);

        return $service;
    }

    private function persistTransaction(): WaveMoneyTransaction {
        /** @var WaveMoneyTransaction $waveMoneyTransaction */
        $waveMoneyTransaction = WaveMoneyTransaction::query()->create([
            'amount' => 100.0,
            'currency' => 'MMK',
            'order_id' => 'order_'.uniqid(),
            'reference_id' => 'ref_'.uniqid(),
            'status' => WaveMoneyTransaction::STATUS_REQUESTED,
            'customer_id' => 1,
        ]);
        $waveMoneyTransaction->transaction()->create([
            'amount' => 100.0,
            'sender' => '+959000000000',
            'message' => 'meter-1',
            'type' => 'energy',
        ]);
        $waveMoneyTransaction->refresh();

        return $waveMoneyTransaction;
    }

    private function callbackData(
        WaveMoneyTransaction $transaction,
        string $status = TransactionCallbackData::STATUS_PAYMENT_CONFIRMED,
        ?float $amount = null,
        ?string $currency = null,
    ): TransactionCallbackData {
        return new TransactionCallbackData(
            $status,
            'merchant-1',
            $transaction->order_id,
            $transaction->reference_id,
            'https://example.test/frontend',
            'https://example.test/backend',
            '+959000000000',
            $amount ?? (float) $transaction->amount,
            300,
            'Energy purchase',
            $currency ?? $transaction->currency,
            'hash',
            'wave-txn-7788',
            'payment-request-1',
            '2026-08-03T12:00:00Z',
            null,
            null,
            null,
            null,
            null,
        );
    }

    public function testConfirmedPaymentCreditsPaymentAndDispatchesProcessing(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyCallback($transaction, $this->callbackData($transaction), self::COMPANY_ID);

        $this->assertSame(WaveMoneyTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
        $this->assertSame('wave-txn-7788', $transaction->fresh()->external_transaction_id);
        $this->assertSame(1, $transaction->fresh()->attempts);
        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
    }

    public function testReplayedCallbackCreditsOnlyOnce(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService();

        $service->applyCallback($transaction, $this->callbackData($transaction), self::COMPANY_ID);
        $service->applyCallback($transaction, $this->callbackData($transaction), self::COMPANY_ID);
        $service->applyCallback($transaction->fresh(), $this->callbackData($transaction), self::COMPANY_ID);

        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
        $this->assertSame(WaveMoneyTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
    }

    public function testLateFailureCallbackDoesNotDowngradeASettledPayment(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService();

        $service->applyCallback($transaction, $this->callbackData($transaction), self::COMPANY_ID);
        $service->applyCallback(
            $transaction->fresh(),
            $this->callbackData($transaction, TransactionCallbackData::STATUS_BILL_COLLECTION_FAILED),
            self::COMPANY_ID
        );

        $this->assertSame(WaveMoneyTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
    }

    /**
     * An unverifiable callback is not a failed payment, so the status is left as it was and a
     * conflict is recorded for an operator to pick up.
     */
    public function testCallbackWithMismatchedAmountRefusesCreditAndRecordsAConflict(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyCallback(
            $transaction,
            $this->callbackData($transaction, amount: 5.0),
            self::COMPANY_ID
        );

        $this->assertSame(WaveMoneyTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);

        $conflicts = $transaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame('Wave Money amount does not match the stored transaction.', $conflicts->first()->state);
    }

    public function testCallbackWithMismatchedCurrencyRefusesCreditAndRecordsAConflict(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyCallback(
            $transaction,
            $this->callbackData($transaction, currency: 'USD'),
            self::COMPANY_ID
        );

        $this->assertSame(WaveMoneyTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);

        $conflicts = $transaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame('Wave Money currency does not match the stored transaction.', $conflicts->first()->state);
    }

    /**
     * A refused callback still counts as a delivery attempt.
     */
    public function testRefusedCallbackStillCountsTheAttempt(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyCallback(
            $transaction,
            $this->callbackData($transaction, amount: 5.0),
            self::COMPANY_ID
        );

        $this->assertSame(1, $transaction->fresh()->attempts);
    }

    public function testFailedCallbackMarksFailed(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $this->makeService()->applyCallback(
            $transaction,
            $this->callbackData($transaction, TransactionCallbackData::STATUS_INSUFFICIENT_BALANCE),
            self::COMPANY_ID
        );

        $this->assertSame(WaveMoneyTransaction::STATUS_FAILED, $transaction->fresh()->status);
        $this->assertSame(1, $transaction->fresh()->attempts);
        Bus::assertNotDispatched(ProcessPayment::class);
    }
}
