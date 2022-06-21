<?php

namespace Inensus\SwiftaPaymentProvider\Services;


use App\Misc\TransactionDataContainer;
use App\Models\Address\Address;
use App\Models\AssetPerson;
use App\Models\AssetRate;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction\TransactionConflicts;

class SwiftaTransactionService
{
    private $swiftaTransaction;
    private $transaction;
    private $address;
    private $meter;
    private $owner;
    private $assetPerson;
    private $assetRate;

    public function __construct(
        SwiftaTransaction $swiftaTransaction,
        Transaction $transaction,
        Address $address,
        Meter $meter,
        Person $owner,
        AssetPerson $assetPerson,
        AssetRate $assetRate
    ) {
        $this->swiftaTransaction = $swiftaTransaction;
        $this->transaction = $transaction;
        $this->address = $address;
        $this->meter = $meter;
        $this->owner = $owner;
        $this->assetPerson = $assetPerson;
        $this->assetRate = $assetRate;
    }

    public function assignIncomingDataToSwiftaTransaction(array $data)
    {
        $transactionReference = null;
        if (array_key_exists('transaction_reference', $data)) {
            $transactionReference = $data['transaction_reference'];
        }
        return $this->swiftaTransaction->newQuery()->create([
            'transaction_reference' => $transactionReference,
            'amount' => $data['amount'],
            'cipher' => $data['cipher'],
            'timestamp' => $data['timestamp'],
        ]);
    }

    public function assignIncomingDataToTransaction(array $data)
    {
        $meterSerial = $data['meter_number'];
        try {
            $sender = $this->getTransactionSender($meterSerial);
            return $this->transaction->newQuery()->make([
                'amount' => (int)$data['amount'],
                'sender' => $sender,
                'message' => $data['meter_number'],
                'type' => 'energy',
                'original_transaction_type' => 'swifta_transaction',
            ]);
        } catch (\Exception $exception) {
            throw  new \Exception($exception->getMessage());
        }
    }

    public function associateSwiftaTransactionWithTransaction($swiftaTransaction, $transaction)
    {
        return $swiftaTransaction->transaction()->save($transaction);
    }

    public function validateInComingTransaction($transactionData)
    {

        $meterSerial = $transactionData['meter_number'];

        try {
            $sender = $this->getTransactionSender($meterSerial);
        } catch (\Exception $exception) {
            throw  new \Exception($exception->getMessage());
        }
        $swiftaTransaction = $this->swiftaTransaction->newQuery()->make([
            'transaction_reference' => null,
            'amount' => $transactionData['amount'],
            'cipher' => $transactionData['cipher'],
            'timestamp' => $transactionData['timestamp'],
        ]);

        $transaction = $this->transaction->newQuery()->make([
            'amount' => (int)$transactionData['amount'],
            'sender' => $sender,
            'message' => $meterSerial,
            'type' => 'energy',
            'original_transaction_type' => 'swifta_transaction',
        ]);

        $transaction->originalTransaction()->associate($swiftaTransaction);
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

    private function getTransactionSender($meterSerial)
    {
        $meterParameter = MeterParameter::query()->whereHas('meter', function ($q) use ($meterSerial) {
            $q->where('serial_number', $meterSerial);
        })->first();
        $personId = $meterParameter->owner_id;
        $this->owner = $meterParameter->owner;
        try {
            $address = Address::query()->whereHasMorph('owner', [Person::class], function ($q) use ($personId) {
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

    public function setStatusPending($transaction)
    {

        $swiftaTransaction = $transaction->originalTransaction()->first();
        $swiftaTransaction->update([
            'status' => 0
        ]);
    }

    public function setUnProcessedTransactionsStatusAsRejected()
    {
        $this->swiftaTransaction->newQuery()->where('status', -2)->get()->each(function ($transaction) {
            $transaction->update([
                'status' => -1
            ]);
            $message= "The transaction that stayed as Unprocessed more than 24 hours, updated to canceled.";
            $conflict = new TransactionConflicts();
            $conflict->state = $message;
            $conflict->transaction()->associate($transaction);
            $conflict->save();
            Log::debug($message." Transaction Id : {$transaction->id}");
        });
    }

    private function processTransaction(TransactionDataContainer $transactionData)
    {
        $transactionData = $this->payAccessRate($transactionData);
        return $this->handleSocialTariffPiggyBankSavingsIfMeterHas($transactionData);
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

    private function payAccessRate(TransactionDataContainer $transactionData)
    {
        $accessRatePayment = $transactionData->meter->accessRatePayment()->first();

        if ($accessRatePayment === null) {
            $debt_amount = 0;
        } else {
            $debt_amount = $accessRatePayment->debt;
        }
        $transactionData->transaction->amount -= $debt_amount;
        return $transactionData;
    }

    private function handleSocialTariffPiggyBankSavingsIfMeterHas($transactionData)
    {
        $meterParameter = $transactionData->meterParameter;
        $bankAccount = $meterParameter->socialTariffPiggyBank()->first();
        if ($bankAccount) {
            $savingsCost = $bankAccount->savings * (($bankAccount->socialTariff->price / 1000) / 100);
            $transactionData->transaction->amount -= $savingsCost;
            return $transactionData;
        }

        return $transactionData;
    }

}

