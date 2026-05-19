<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Tests\Unit;

use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApiService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalIpnService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class PesapalIpnServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    protected function setUp(): void {
        parent::setUp();
        Log::spy();
    }

    public function testIgnoresPayloadStatusAndTriggersSuccessHandlerOnStatusCodeOne(): void {
        $transaction = $this->createPersistedTransaction();

        $apiService = $this->createMock(PesapalApiService::class);
        $apiService->expects($this->once())
            ->method('getTransactionStatus')
            ->with('ot_abc')
            ->willReturn($this->statusResult(1, 'COMPLETED', 'CONFIRM_42'));

        $transactionService = $this->createMock(PesapalTransactionService::class);
        $transactionService->method('getByOrderTrackingId')->with('ot_abc')->willReturn($transaction);
        $transactionService->expects($this->once())
            ->method('processSuccessfulPayment')
            ->with(42, $transaction);
        $transactionService->expects($this->never())->method('processFailedPayment');

        $service = new PesapalIpnService($apiService, $transactionService);

        $request = Request::create('/api/pesapal/ipn/42', 'GET', [
            'OrderTrackingId' => 'ot_abc',
            'status_code' => 2,
            'status' => 'FAILED',
        ]);

        $this->assertTrue($service->processIpn($request, 42));
        $this->assertSame('CONFIRM_42', $transaction->fresh()->external_transaction_id);
    }

    public function testTriggersFailedHandlerOnStatusCodeTwo(): void {
        $transaction = $this->createPersistedTransaction();

        $apiService = $this->createMock(PesapalApiService::class);
        $apiService->method('getTransactionStatus')->willReturn($this->statusResult(2, 'FAILED'));

        $transactionService = $this->createMock(PesapalTransactionService::class);
        $transactionService->method('getByOrderTrackingId')->willReturn($transaction);
        $transactionService->expects($this->once())->method('processFailedPayment')->with($transaction);
        $transactionService->expects($this->never())->method('processSuccessfulPayment');

        $service = new PesapalIpnService($apiService, $transactionService);
        $request = Request::create('/api/pesapal/ipn/42', 'GET', ['OrderTrackingId' => 'ot_abc']);

        $this->assertTrue($service->processIpn($request, 42));
    }

    public function testReturnsFalseAndDoesNothingForUnknownOrderTrackingId(): void {
        $apiService = $this->createMock(PesapalApiService::class);
        $apiService->expects($this->never())->method('getTransactionStatus');

        $transactionService = $this->createMock(PesapalTransactionService::class);
        $transactionService->method('getByOrderTrackingId')->willReturn(null);

        $service = new PesapalIpnService($apiService, $transactionService);
        $request = Request::create('/api/pesapal/ipn/42', 'GET', ['OrderTrackingId' => 'unknown']);

        $this->assertFalse($service->processIpn($request, 42));
    }

    public function testReturnsFalseWhenStatusQueryFails(): void {
        $transaction = $this->createPersistedTransaction();

        $apiService = $this->createMock(PesapalApiService::class);
        $apiService->method('getTransactionStatus')->willReturn([
            'status_code' => null,
            'status_description' => '',
            'amount' => 0.0,
            'currency' => '',
            'payment_method' => '',
            'confirmation_code' => '',
            'merchant_reference' => '',
            'error' => 'network error',
        ]);

        $transactionService = $this->createMock(PesapalTransactionService::class);
        $transactionService->method('getByOrderTrackingId')->willReturn($transaction);
        $transactionService->expects($this->never())->method('processSuccessfulPayment');
        $transactionService->expects($this->never())->method('processFailedPayment');

        $service = new PesapalIpnService($apiService, $transactionService);
        $request = Request::create('/api/pesapal/ipn/42', 'GET', ['OrderTrackingId' => 'ot_abc']);

        $this->assertFalse($service->processIpn($request, 42));
    }

    private function createPersistedTransaction(): PesapalTransaction {
        return PesapalTransaction::query()->create([
            'amount' => 100.0,
            'currency' => 'KES',
            'order_id' => 'order_test_'.uniqid(),
            'reference_id' => 'ref_test_'.uniqid(),
            'status' => PesapalTransaction::STATUS_REQUESTED,
            'customer_id' => 1,
            'order_tracking_id' => 'ot_abc',
            'merchant_reference' => 'mr_abc',
        ]);
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
