<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Tests\Unit;

use App\Jobs\ProcessPayment;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\PaystackPaymentProvider\Services\PaystackWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class PaystackWebhookServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    private const int COMPANY_ID = 42;

    private function makeService(): PaystackWebhookService {
        /** @var PaystackWebhookService $service */
        $service = $this->app->make(PaystackWebhookService::class);

        return $service;
    }

    private function persistTransaction(): PaystackTransaction {
        /** @var PaystackTransaction $paystackTransaction */
        $paystackTransaction = PaystackTransaction::query()->create([
            'amount' => 100.0,
            'currency' => 'NGN',
            'order_id' => 'order_'.uniqid(),
            'reference_id' => 'ref_'.uniqid(),
            'status' => PaystackTransaction::STATUS_REQUESTED,
            'customer_id' => 1,
            'paystack_reference' => 'pr_'.uniqid(),
        ]);
        $paystackTransaction->transaction()->create([
            'amount' => 100.0,
            'sender' => '+2348000000000',
            'message' => 'meter-1',
            'type' => 'energy',
        ]);
        $paystackTransaction->refresh();

        return $paystackTransaction;
    }

    /**
     * @param array<string, mixed> $dataOverrides
     */
    private function chargeRequest(
        PaystackTransaction $transaction,
        string $event = 'charge.success',
        array $dataOverrides = [],
    ): Request {
        return Request::create(
            '/paystack/webhook/'.self::COMPANY_ID,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'event' => $event,
                'data' => array_merge([
                    'id' => 998877,
                    'reference' => $transaction->paystack_reference,
                    // Paystack reports minor units (kobo).
                    'amount' => (int) ($transaction->amount * 100),
                    'currency' => $transaction->currency,
                ], $dataOverrides),
            ]),
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
        $this->assertSame(PaystackTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
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
        $this->assertSame(PaystackTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
    }

    public function testLateChargeFailedDoesNotDowngradeASettledPayment(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();
        $service = $this->makeService();

        $service->processWebhook($this->chargeRequest($transaction), self::COMPANY_ID);
        $service->processWebhook(
            $this->chargeRequest($transaction->fresh(), 'charge.failed'),
            self::COMPANY_ID
        );

        $this->assertSame(PaystackTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
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
            $this->chargeRequest($transaction, dataOverrides: ['amount' => 500]),
            self::COMPANY_ID
        );

        $this->assertFalse($processed);
        $this->assertSame(PaystackTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);

        $conflicts = $transaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame('Paystack amount does not match the stored transaction.', $conflicts->first()->state);
    }

    public function testChargeWithMismatchedCurrencyRefusesCreditAndRecordsAConflict(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->chargeRequest($transaction, dataOverrides: ['currency' => 'GHS']),
            self::COMPANY_ID
        );

        $this->assertFalse($processed);
        $this->assertSame(PaystackTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);

        $conflicts = $transaction->conflicts()->get();
        $this->assertCount(1, $conflicts);
        $this->assertSame('Paystack currency does not match the stored transaction.', $conflicts->first()->state);
    }

    public function testChargeForUnknownReferenceIsIgnored(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->chargeRequest($transaction, dataOverrides: ['reference' => 'pr_does_not_exist']),
            self::COMPANY_ID
        );

        $this->assertFalse($processed);
        $this->assertSame(PaystackTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);
    }

    public function testUnrelatedEventIsIgnored(): void {
        Bus::fake();
        $transaction = $this->persistTransaction();

        $processed = $this->makeService()->processWebhook(
            $this->chargeRequest($transaction, 'transfer.success'),
            self::COMPANY_ID
        );

        $this->assertFalse($processed);
        $this->assertSame(PaystackTransaction::STATUS_REQUESTED, $transaction->fresh()->status);
        Bus::assertNotDispatched(ProcessPayment::class);
    }
}
