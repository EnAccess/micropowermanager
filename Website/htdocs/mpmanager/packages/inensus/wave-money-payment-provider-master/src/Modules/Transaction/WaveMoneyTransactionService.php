<?php
declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Transaction;

use App\Models\Meter\Meter;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\WaveMoneyApiService;
use Ramsey\Uuid\Uuid;

class WaveMoneyTransactionService
{

    public function __construct(
        private Meter $meter,
        private WaveMoneyApiService $apiService,
    )
    {

    }

    public function initializeTransactionRequest(string $meterSerialNumber): void
    {
        $meter = $this->meter->findBySerialNumber($meterSerialNumber);
        // try to find the customer data based on the request data
        $customerId = $meter->MeterParameter->owner_id;

        $orderId = Uuid::uuid4()->toString(); // need to store somewhere
        $referenceId = Uuid::uuid4()->toString(); // need to store somewhere
        $transaction = new WaveMoneyTransaction();
        $transaction->setOrderId($orderId);
        $transaction->setReferenceId($referenceId);
        $transaction->setCustomerId($customerId);
        $transaction->setMeterSerial($meterSerialNumber);
        $transaction->setStatus(WaveMoneyTransaction::STATUS_REQUESTED);
        $transaction->save();

        $this->apiService->requestPayment($transaction);
    }
}
