<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Events\TransactionFailedEvent;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

/**
 * A payment that was accepted and then failed during processing is money in with no value out, so
 * TransactionFailedListener records a conflict for it. That routes through TransactionAdapter,
 * which silently returns null for any transaction type it does not map — these pin the two that
 * were missing.
 */
class TransactionFailedConflictTest extends TestCase {
    use RefreshMultipleDatabases;

    private function pesapalTransaction(): PesapalTransaction {
        /** @var PesapalTransaction $pesapalTransaction */
        $pesapalTransaction = PesapalTransaction::query()->create([
            'amount' => 100.0,
            'currency' => 'KES',
            'order_id' => 'order_'.uniqid(),
            'reference_id' => 'ref_'.uniqid(),
            'status' => PesapalTransaction::STATUS_SUCCESS,
            'customer_id' => 1,
            'order_tracking_id' => 'ot_'.uniqid(),
            'merchant_reference' => 'mr_'.uniqid(),
        ]);
        $pesapalTransaction->transaction()->create([
            'amount' => 100.0,
            'sender' => '+254700000000',
            'message' => 'meter-1',
            'type' => 'energy',
        ]);

        return $pesapalTransaction->refresh();
    }

    private function cashTransaction(): CashTransaction {
        /** @var CashTransaction $cashTransaction */
        $cashTransaction = CashTransaction::query()->create(['user_id' => 1]);
        $cashTransaction->transaction()->create([
            'amount' => 100.0,
            'sender' => 'User-1',
            'message' => 'meter-1',
            'type' => 'deferred_payment',
        ]);

        return $cashTransaction->refresh();
    }

    public function testFailedPesapalTransactionRecordsAConflict(): void {
        $pesapalTransaction = $this->pesapalTransaction();
        /** @var Transaction $transaction */
        $transaction = $pesapalTransaction->transaction()->first();

        event(new TransactionFailedEvent($transaction, 'Token generation failed'));

        $conflicts = $pesapalTransaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame('Token generation failed', $conflicts->first()->state);
    }

    public function testFailedCashTransactionRecordsAConflict(): void {
        $cashTransaction = $this->cashTransaction();
        /** @var Transaction $transaction */
        $transaction = $cashTransaction->transaction()->first();

        event(new TransactionFailedEvent($transaction, 'Minimum purchase amount not reached'));

        $conflicts = $cashTransaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame('Minimum purchase amount not reached', $conflicts->first()->state);
    }

    /**
     * Manufacturer APIs fail with Guzzle messages that carry the verb, full URL, status line and a
     * response-body excerpt, which runs well past the width `state` was originally given.
     */
    public function testFailedTransactionRecordsAConflictMessageLongerThanTheOldColumnWidth(): void {
        $cashTransaction = $this->cashTransaction();
        /** @var Transaction $transaction */
        $transaction = $cashTransaction->transaction()->first();
        $message = 'Manufacturer Api did not succeed after 3 times with the following error: '
            .'Client error: `POST https://assetcontrol.central.glpapps.com/v2/token` resulted in a '
            .'`404 Not Found` response: {"message":"Device not found","details":"'.str_repeat('x', 200).'"}';

        event(new TransactionFailedEvent($transaction, $message));

        $conflicts = $cashTransaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame($message, $conflicts->first()->state);
    }

    public function testAConflictMessageIsCappedSoOneRunawayErrorCannotDominateTheRow(): void {
        $cashTransaction = $this->cashTransaction();
        /** @var Transaction $transaction */
        $transaction = $cashTransaction->transaction()->first();

        event(new TransactionFailedEvent($transaction, str_repeat('y', 5000)));

        $state = $cashTransaction->conflicts()->first()->state;
        $this->assertStringStartsWith(str_repeat('y', 1000), $state);
        $this->assertLessThan(1100, strlen($state));
    }

    /**
     * A failure must not undo the settlement — PesaPal's sendResult(false) only logs.
     */
    public function testFailedPesapalTransactionKeepsItsSettledStatus(): void {
        $pesapalTransaction = $this->pesapalTransaction();
        /** @var Transaction $transaction */
        $transaction = $pesapalTransaction->transaction()->first();

        event(new TransactionFailedEvent($transaction, 'Token generation failed'));

        $this->assertSame(PesapalTransaction::STATUS_SUCCESS, $pesapalTransaction->fresh()->status);
    }
}
