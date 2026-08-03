<?php

declare(strict_types=1);

namespace App\Plugins\SwiftaPaymentProvider\Tests\Unit;

use App\Jobs\ProcessPayment;
use App\Models\Transaction\Transaction;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use App\Plugins\SwiftaPaymentProvider\Services\SwiftaTransactionService;
use Illuminate\Support\Facades\Bus;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class SwiftaTransactionCallbackTest extends TestCase {
    use RefreshMultipleDatabases;

    private const int COMPANY_ID = 42;

    private function makeService(): SwiftaTransactionService {
        /** @var SwiftaTransactionService $service */
        $service = $this->app->make(SwiftaTransactionService::class);

        return $service;
    }

    private function persistTransaction(): SwiftaTransaction {
        /** @var SwiftaTransaction $swiftaTransaction */
        $swiftaTransaction = SwiftaTransaction::query()->create([
            'amount' => 100.0,
            'cipher' => md5('Inensus1754200000100Swifta'),
            'timestamp' => '1754200000',
            'status' => SwiftaTransaction::STATUS_REQUESTED,
        ]);
        $swiftaTransaction->transaction()->create([
            'amount' => 100.0,
            'sender' => '+2348000000000',
            'message' => 'meter-1',
            'type' => 'energy',
        ]);
        $swiftaTransaction->refresh();

        return $swiftaTransaction;
    }

    private function mpmTransaction(SwiftaTransaction $swiftaTransaction): Transaction {
        /** @var Transaction $transaction */
        $transaction = $swiftaTransaction->transaction()->first();

        return $transaction;
    }

    public function testCallbackCreditsPaymentAndDispatchesProcessing(): void {
        Bus::fake();
        $swiftaTransaction = $this->persistTransaction();

        $this->makeService()->applyCallback(
            $this->mpmTransaction($swiftaTransaction),
            'swifta-ref-991',
            self::COMPANY_ID
        );

        $this->assertSame(SwiftaTransaction::STATUS_SUCCESS, $swiftaTransaction->fresh()->status);
        $this->assertSame('swifta-ref-991', $swiftaTransaction->fresh()->transaction_reference);
        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
    }

    /**
     * Swifta's cipher covers only the timestamp and amount, so a captured callback stays valid
     * indefinitely — replaying it must not credit the device again.
     */
    public function testReplayedCallbackCreditsOnlyOnce(): void {
        Bus::fake();
        $swiftaTransaction = $this->persistTransaction();
        $service = $this->makeService();
        $transaction = $this->mpmTransaction($swiftaTransaction);

        $service->applyCallback($transaction, 'swifta-ref-991', self::COMPANY_ID);
        $service->applyCallback($transaction, 'swifta-ref-991', self::COMPANY_ID);
        $service->applyCallback($transaction, 'swifta-ref-991', self::COMPANY_ID);

        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
        $this->assertSame(SwiftaTransaction::STATUS_SUCCESS, $swiftaTransaction->fresh()->status);
    }

    public function testReplayWithADifferentReferenceStillCreditsOnlyOnce(): void {
        Bus::fake();
        $swiftaTransaction = $this->persistTransaction();
        $service = $this->makeService();
        $transaction = $this->mpmTransaction($swiftaTransaction);

        $service->applyCallback($transaction, 'swifta-ref-991', self::COMPANY_ID);
        $service->applyCallback($transaction, 'swifta-ref-992', self::COMPANY_ID);

        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
        // The later reference is still recorded; only the crediting is suppressed.
        $this->assertSame('swifta-ref-992', $swiftaTransaction->fresh()->transaction_reference);
    }

    public function testCallbackForATransactionWithoutASwiftaRecordIsRejected(): void {
        Bus::fake();
        $swiftaTransaction = $this->persistTransaction();
        $orphan = $this->mpmTransaction($swiftaTransaction);
        $swiftaTransaction->delete();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Transaction {$orphan->id} has no Swifta transaction to settle.");

        try {
            $this->makeService()->applyCallback($orphan, 'swifta-ref-991', self::COMPANY_ID);
        } finally {
            Bus::assertNotDispatched(ProcessPayment::class);
        }
    }

    public function testCheckAmountIsSameRejectsAMismatchedAmount(): void {
        $swiftaTransaction = $this->persistTransaction();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('amount validation field.');

        $this->makeService()->checkAmountIsSame(5, $this->mpmTransaction($swiftaTransaction));
    }
}
