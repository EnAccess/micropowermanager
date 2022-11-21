<?php

namespace App\Services;

use App\Misc\TransactionDataContainer;
use App\Models\Address\Address;
use App\Models\BaseModel;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Inensus\SteamaMeter\Exceptions\ModelNotFoundException;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\WaveMoneyApiService;
use Ramsey\Uuid\Uuid;

abstract class AbstractPaymentAggregatorTransactionService
{
    protected string $payerPhoneNumber;
    protected string $meterSerialNumber;

    public function __construct(
        private Meter $meter,
        private Person $owner,
        private Address $address,
        private Transaction $transaction,
        private MeterParameter $meterParameter,
        private BaseModel $paymentAggregatorTransactionModel,
    ) {

    }

    public function validatePaymentOwner(string $meterSerialNumber)
    {
        if (!$meter = $this->meter->findBySerialNumber($meterSerialNumber)) {
            throw new ModelNotFoundException('Meter not found with serial number: ' . $meterSerialNumber);
        }

        if (!$customerId = $meter->MeterParameter->owner_id) {
            throw new ModelNotFoundException('Customer not found with meter serial number: ' . $meterSerialNumber);
        }

        $this->meterSerialNumber = $meterSerialNumber;

        try {
            $this->payerPhoneNumber = $this->getTransactionSender($meterSerialNumber);
        } catch (\Exception $exception) {
            throw  new \Exception($exception->getMessage());
        }

    }

    public function imitateTransactionForValidation(array $transactionData)
    {
        $paymentAggregatorTransaction = $this->paymentAggregatorTransactionModel->newQuery()->make($transactionData);

        $transaction = $this->transaction->newQuery()->make([
            'amount' => (int)$transactionData['amount'],
            'sender' => $this->payerPhoneNumber,
            'message' => $this->meterSerialNumber,
            'type' => 'energy',
            'original_transaction_type' => $this->paymentAggregatorTransactionModel::class,
        ]);

        $transaction->originalTransaction()->associate($paymentAggregatorTransaction);

        try {
            $transactionData = TransactionDataContainer::initialize($transaction);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        $transactionData = $this->payLoanDebt($transactionData);
        $transactionData = $this->processTransaction($transactionData);
        if ($transactionData->transaction->amount < 0) {
            throw new \Exception('Amount validation field.');
        }
    }

    private function getTransactionSender($meterSerialNumber)
    {
        $meterParameter = $this->meterParameter->newQuery()
            ->whereHas('meter',
                function ($q) use ($meterSerialNumber) {
                    $q->where('serial_number', $meterSerialNumber);
                })->first();

        $personId = $meterParameter->owner_id;
        $this->owner = $meterParameter->owner;
        try {
            $address = $this->address->newQuery()
                ->whereHasMorph('owner', [Person::class],
                    function ($q) use ($personId) {
                        $q->where('owner_id', $personId);
                    })->where('is_primary', 1)->firstOrFail();
            return $address->phone;
        } catch (ModelNotFoundException $exception) {
            throw new \Exception('No phone number record found by customer.');
        }
    }
    private function payLoanDebt(TransactionDataContainer $transactionData): TransactionDataContainer
    {

        $loans = $this->getCustomerDueRates($this->owner);
        foreach ($loans as $loan) {
            $transactionData->transaction->amount -= $loan->remaining;
        }

        return $transactionData;
    }
    private function getCustomerDueRates($owner)
    {
        $loans = $this->assetPerson->newQuery()->where('person_id', $owner->id)->pluck('id');
        return $this->assetRate->newQuery()->with('assetPerson.assetType')
            ->whereIn('asset_person_id', $loans)
            ->where('remaining', '>', 0)
            ->whereDate('due_date', '<', date('Y-m-d'))
            ->get();
    }
}