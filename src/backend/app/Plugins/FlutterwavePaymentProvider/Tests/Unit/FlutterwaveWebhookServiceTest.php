<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Tests\Unit;

use App\Jobs\ProcessPayment;
use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class FlutterwaveWebhookServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    private const int COMPANY_ID = 42;

    private function makeService(): FlutterwaveWebhookService {
        /** @var FlutterwaveWebhookService $service */
        $service = $this->app->make(FlutterwaveWebhookService::class);

        return $service;
    }

    private function persistTransaction(): FlutterwaveTransaction {
        /** @var FlutterwaveTransaction $flutterwaveTransaction */
        $flutterwaveTransaction = FlutterwaveTransaction::query()->create([
            'amount' => 100.0,
            'currency' => 'NGN',
            'order_id' => 'order_'.uniqid(),
            'reference_id' => 'ref_'.uniqid(),
            'status' => FlutterwaveTransaction::STATUS_REQUESTED,
            'customer_id' => 1,
            'flutterwave_reference' => 'fw_'.uniqid(),
        ]);
        $flutterwaveTransaction->transaction()->create([
            'amount' => 100.0,
            'sender' => '+2348000000000',
            'message' => 'meter-1',
            'type' => 'energy',
        ]);
        $flutterwaveTransaction->refresh();

        return $flutterwaveTransaction;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function chargeRequest(
        FlutterwaveTransaction $transaction,
        string $status = 'successful',
        array $overrides = [],
    ): Request {
        // Flutterwave's webhook body is flat (no event/data wrapper) — confirmed
        // against a real delivery, see FlutterwaveWebhookService's docblock.
        return Request::create(
            '/flutterwave/webhook/'.self::COMPANY_ID,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(array_merge([
                'id' => 998877,
                'txRef' => $transaction->flutterwave_reference,
                'status' => $status,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
            ], $overrides)),
        );
    }

    public function testChargeSuccessCreditsPaymentAndDispatchesProcessing(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->chargeRequest($transaction),
            self::COMPANY_ID
        );

        $this->assertTrue($processed);
        $this->assertSame(FlutterwaveTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
        $this->assertSame('998877', $transaction->fresh()->external_transaction_id);
        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
    }

    public function testReplayedChargeSuccessCreditsOnlyOnce(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService();

        $service->processWebhook($this->chargeRequest($transaction), self::COMPANY_ID);
        $service->processWebhook($this->chargeRequest($transaction), self::COMPANY_ID);
        $service->processWebhook($this->chargeRequest($transaction->fresh()), self::COMPANY_ID);

        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
        $this->assertSame(FlutterwaveTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
    }

    public function testLateChargeFailedDoesNotDowngradeASettledPayment(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService();

        $service->processWebhook($this->chargeRequest($transaction), self::COMPANY_ID);
        $service->processWebhook(
            $this->chargeRequest($transaction->fresh(), 'failed'),
            self::COMPANY_ID
        );

        $this->assertSame(FlutterwaveTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
    }

    /**
     * An unverifiable charge is not a failed payment, so the status is left as it was and a
     * conflict is recorded for an operator to pick up.
     */
    public function testChargeWithTamperedAmountRefusesCreditAndRecordsAConflict(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->chargeRequest($transaction, overrides: ['amount' => 500]),
            self::COMPANY_ID
        );

        $this->assertFalse($processed);
        $this->assertSame(FlutterwaveTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);

        $conflicts = $transaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame('Flutterwave amount does not match the stored transaction.', $conflicts->first()->state);
    }

    public function testChargeWithMismatchedCurrencyRefusesCreditAndRecordsAConflict(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->chargeRequest($transaction, overrides: ['currency' => 'GHS']),
            self::COMPANY_ID
        );

        $this->assertFalse($processed);
        $this->assertSame(FlutterwaveTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);

        $conflicts = $transaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame('Flutterwave currency does not match the stored transaction.', $conflicts->first()->state);
    }

    /**
     * Flutterwave retries a webhook it got no 2xx for, so the same mismatch arrives repeatedly.
     */
    public function testRepeatedMismatchedChargeRecordsASingleConflict(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService();

        $service->processWebhook($this->chargeRequest($transaction, overrides: ['amount' => 500]), self::COMPANY_ID);
        $service->processWebhook($this->chargeRequest($transaction, overrides: ['amount' => 500]), self::COMPANY_ID);
        $service->processWebhook($this->chargeRequest($transaction, overrides: ['amount' => 500]), self::COMPANY_ID);

        $this->assertCount(1, $transaction->conflicts()->get());
        Bus::assertNotDispatched(ProcessPayment::class);
    }

    public function testChargeFailedMarksTheTransactionFailed(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->chargeRequest($transaction, 'failed'),
            self::COMPANY_ID
        );

        $this->assertTrue($processed);
        $this->assertSame(FlutterwaveTransaction::STATUS_FAILED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);
    }

    public function testChargeForUnknownReferenceIsIgnored(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->chargeRequest($transaction, overrides: ['txRef' => 'fw_does_not_exist']),
            self::COMPANY_ID
        );

        $this->assertFalse($processed);
        $this->assertSame(FlutterwaveTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function v3ChargeRequest(
        FlutterwaveTransaction $transaction,
        string $status = 'successful',
        array $overrides = [],
    ): Request {
        // The "v3 webhook" toggle on Flutterwave's dashboard sends this nested,
        // snake_case shape instead — see normalizePayload()'s docblock.
        return Request::create(
            '/flutterwave/webhook/'.self::COMPANY_ID,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'data' => array_merge([
                    'id' => 998877,
                    'tx_ref' => $transaction->flutterwave_reference,
                    'status' => $status,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                ], $overrides),
                'event' => 'charge.completed',
                'event.type' => 'CARD_TRANSACTION',
            ]),
        );
    }

    public function testV3ChargeSuccessCreditsPaymentAndDispatchesProcessing(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->v3ChargeRequest($transaction),
            self::COMPANY_ID
        );

        $this->assertTrue($processed);
        $this->assertSame(FlutterwaveTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
        $this->assertSame('998877', $transaction->fresh()->external_transaction_id);
        Bus::assertDispatchedTimes(ProcessPayment::class, 1);
    }

    public function testV3ChargeWithTamperedAmountRefusesCreditAndRecordsAConflict(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->v3ChargeRequest($transaction, overrides: ['amount' => 500]),
            self::COMPANY_ID
        );

        $this->assertFalse($processed);
        $this->assertSame(FlutterwaveTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);

        $conflicts = $transaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame('Flutterwave amount does not match the stored transaction.', $conflicts->first()->state);
    }

    public function testV3ChargeFailedMarksTheTransactionFailed(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->v3ChargeRequest($transaction, 'failed'),
            self::COMPANY_ID
        );

        $this->assertTrue($processed);
        $this->assertSame(FlutterwaveTransaction::STATUS_FAILED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);
    }
}
