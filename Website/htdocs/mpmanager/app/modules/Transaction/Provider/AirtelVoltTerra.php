<?php

namespace MPM\Transaction\Provider;

use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class AirtelVoltTerra implements ITransactionProvider
{

    private $validData;

    /**
     * DI will initialize the needed models
     *
     * @param \App\Models\Transaction\AirtelTransaction $airtelTransaction
     * @param Transaction $transaction
     */
    public function __construct(
        private \App\Models\Transaction\AirtelTransaction $airtelTransaction,
        private Transaction $transaction
    ) {

    }

    public function saveTransaction(): void
    {
        $this->airtelTransaction = new \App\Models\Transaction\AirtelTransaction();
        $this->transaction = new Transaction();
        //assign data
        $this->assignData();

        //save transaction
        $this->saveData($this->airtelTransaction);
    }

    /**
     * @param bool $requestType
     * @param Transaction $transaction
     */
    public function sendResult(bool $requestType, Transaction $transaction): void
    {
        if ($requestType) {
            $this->airtelTransaction->status = 1;
            $this->airtelTransaction->save();
            $smsService = app()->make(SmsService::class);
            $smsService->sendSms($transaction, SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
        } else {
            $this->airtelTransaction->status = -1;
            $this->airtelTransaction->save();
        }
    }

    public function init($transaction): void
    {
        $this->airtelTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function validateRequest($request): bool
    {
        $meterSerial = $request['meterSerial'];
        $amount = $request['amount'];
        $validator = Validator::make([
            'meterSerial' => $meterSerial,
            'amount' => $amount
        ], [
            'meterSerial' => 'required',
            'amount' => 'required'
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $meterModel = new Meter();

        if (!$meter = $meterModel->findBySerialNumber($meterSerial)) {
            throw new ModelNotFoundException('Meter not found with serial number you entered');
        }

        if (!$meterTariff = $meter->meterParameter->tariff) {
            throw new ModelNotFoundException('Tariff not found with meter serial number you entered');
        }

        $customerId = $meter->MeterParameter->owner_id;

        if (!$customerId) {
            throw new ModelNotFoundException('Customer not found with meter serial number you entered');
        }
        $minimumPurchaseAmount = $meterTariff->minimum_purchase_amount ?? 0;

        if ($amount < $minimumPurchaseAmount) {
            throw new \Exception('Amount is less than minimum purchase amount');
        }

        try {
            $payerPhoneNumber = $this->getTransactionSender($meterSerial);
        } catch (\Exception $exception) {
            throw  new \Exception($exception->getMessage());
        }

        $this->validData = [
            'meterSerial' => $meterSerial,
            'meterId' => $meter->id,
            'amount' => $amount,
            'customerId' => $customerId,
            'phoneNumber' => $payerPhoneNumber,

        ];
        return true;
    }

    private function getTransactionSender($meterSerial)
    {

        $meterParameter = MeterParameter::query()
            ->whereHas(
                'meter',
                function ($q) use ($meterSerial) {
                    $q->where('serial_number', $meterSerial);
                }
            )->first();

        $personId = $meterParameter->owner_id;
        try {
            $address = Address::query()
                ->whereHasMorph(
                    'owner',
                    [Person::class],
                    function ($q) use ($personId) {
                        $q->where('owner_id', $personId);
                    }
                )->where('is_primary', 1)->firstOrFail();
            return $address->phone;
        } catch (ModelNotFoundException $exception) {
            throw new \Exception('No phone number record found by customer.');
        }
    }

    private function assignData(): void
    {
        //provider specific data
        $this->airtelTransaction->interface_id = 'Airtel Transactions Email Interface';
        $this->airtelTransaction->business_number = 'Airtel Transactions Email Interface';
        $this->airtelTransaction->trans_id = 'Airtel Transactions Email Interface';
        $this->airtelTransaction->tr_id = 'Airtel Transactions Email Interface';
        // common transaction data
        $this->transaction->amount = (int)$this->validData['amount'];
        $this->transaction->sender = $this->validData['phoneNumber'];
        $this->transaction->message = $this->validData['meterSerial'];
    }

    public function saveData(\App\Models\Transaction\AirtelTransaction $airtelTransaction): void
    {
        $airtelTransaction->save();
        event('transaction.confirm');
    }

    public function saveCommonData(): Model
    {
        return $this->airtelTransaction->transaction()->save($this->transaction);
    }

    public function addConflict(?string $message): void
    {
        $conflict = new TransactionConflicts();
        $conflict->state = $message;
        $conflict->transaction()->associate($this->airtelTransaction);
        $conflict->save();
    }

    public function confirm(): void
    {
        // TODO: Implement confirm() method.
    }

    public function getMessage(): string
    {
        // TODO: Implement getMessage() method.
    }

    public function getAmount(): int
    {
        // TODO: Implement getAmount() method.
    }

    public function getSender(): string
    {
        // TODO: Implement getSender() method.
    }

    public function getTransaction(): Transaction
    {
        // TODO: Implement getTransaction() method.
    }
}
