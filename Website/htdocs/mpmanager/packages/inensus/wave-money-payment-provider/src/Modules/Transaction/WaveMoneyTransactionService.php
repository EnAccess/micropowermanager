<?php
declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Transaction;

use App\Misc\TransactionDataContainer;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use Inensus\SteamaMeter\Exceptions\ModelNotFoundException;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\WaveMoneyApiService;
use Ramsey\Uuid\Uuid;

class WaveMoneyTransactionService extends AbstractPaymentAggregatorTransactionService
{

    public function __construct(
        private Meter $meter,
        private WaveMoneyApiService $apiService,
        private Person $owner,
        private Address $address,
        private Transaction $transaction,
        private MeterParameter $meterParameter,
    ) {
        parent::__construct($meter, $owner, $address, $transaction, $meterParameter);
    }

    public function initializeTransactionRequest(string $meterSerialNumber, float $amount): void
    {
        $meter = $this->meter->findBySerialNumber($meterSerialNumber);

        // try to find the customer data based on the request data
        $customerId = $meter->MeterParameter->owner_id;

        if (!$customerId) {
            throw new ModelNotFoundException('Customer not found with meter serial number: ' . $meterSerialNumber);
        }

        $orderId = Uuid::uuid4()->toString(); // need to store somewhere
        $referenceId = Uuid::uuid4()->toString(); // need to store somewhere
        $transaction = new WaveMoneyTransaction();
        $transaction->setAmount($amount);
        $transaction->setOrderId($orderId);
        $transaction->setReferenceId($referenceId);
        $transaction->setCustomerId($customerId);
        $transaction->setMeterSerial($meterSerialNumber);
        $transaction->setStatus(WaveMoneyTransaction::STATUS_REQUESTED);
        $transaction->save();

       // $this->apiService->requestPayment($transaction);
    }


}
