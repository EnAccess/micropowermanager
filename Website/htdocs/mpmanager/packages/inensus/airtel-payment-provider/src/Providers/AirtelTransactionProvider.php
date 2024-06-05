<?php

namespace Inensus\AirtelPaymentProvider\Providers;

use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inensus\AirtelPaymentProvider\Services\AirtelTransactionService;
use MPM\Transaction\Provider\ITransactionProvider;
use SimpleXMLElement;
use Illuminate\Validation\ValidationException;

class AirtelTransactionProvider implements ITransactionProvider
{
    private $notifyCustomerViaSms = true;

    private $validData;

    public function __construct(
        private AirtelTransaction $airtelTransaction,
        private Transaction $transaction,
        private AirtelTransactionService $airtelTransactionService
    ) {
    }

    public function saveTransaction()
    {
        $this->airtelTransactionService->saveTransaction();
    }

    public function sendResult(bool $requestType, Transaction $transaction)
    {
        $airtelTransaction = $transaction->originalTransaction()->first();
        if ($requestType) {
            $updateData = [
                'status' => AirtelTransaction::STATUS_SUCCESS
            ];
            $smsService = app()->make(SmsService::class);
            $smsService->sendSms($transaction, SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
        } else {
            $updateData = [
                'status' => AirtelTransaction::STATUS_FAILED
            ];
        }
        $this->airtelTransactionService->update($airtelTransaction, $updateData);
    }

    public function validateRequest($request)
    {
        $transactionXml = new SimpleXMLElement($request);
        $transactionData = json_encode($transactionXml);
        $transactionData = json_decode($transactionData, true);

        Validator::extend('unique_reference1', function ($attribute, $value, $parameters, $validator) {
            return !DB::connection('shard')->table('airtel_transactions')->where('tr_id', $value)->exists();
        });

        $validator = Validator::make($transactionData, [
            'TYPE' => 'required',
            'CUSTOMERMSISDN' => 'required',
            'MERCHANTMSISDN' => 'required',
            'AMOUNT' => 'required',
            'REFERENCE' => 'required',
            'REFERENCE1' => 'required|unique_reference1',
        ], [
            'REFERENCE1.unique_reference1' => 'Duplicate Reference1',
        ]);

        try {
            $validator->validate();
        } catch (ValidationException $e) {
            $errors = $validator->errors();
            if ($errors->has('REFERENCE1')) {
                throw new \Exception('Duplicate Reference1');
            } else {
                $errorMessage = $errors->first();
                throw new \Exception($errorMessage);
            }
        }
        $serialNumber = $transactionData['REFERENCE'];
        $amount = $transactionData['AMOUNT'];

        try {
            $this->airtelTransactionService->validatePaymentOwner($serialNumber, $amount);
            $airtelTransactionData = $this->airtelTransactionService->initializeTransactionData($transactionData);
            // We need to make sure that the payment is fully processable from our end .
            $this->airtelTransactionService->imitateTransactionForValidation($airtelTransactionData, $amount);

        } catch (\Exception $exception) {
            throw  new \Exception($exception->getMessage());
        }

        $this->setValidData($airtelTransactionData);
    }

    public function setValidData($airtelTransactionData)
    {
        $this->validData = $airtelTransactionData;
    }


    public function confirm(): void
    {
        // TODO: Implement confirm() method.
    }

    public function getMessage(): string
    {
        return $this->airtelTransactionService->getSerialNumber();
    }

    public function getAmount(): int
    {
        return $this->airtelTransactionService->getAmount();
    }

    public function getSender(): string
    {
        return $this->airtelTransactionService->getPayerPhoneNumber();
    }

    public function saveCommonData(): Model
    {
        return $this->airtelTransaction->transaction()->save($this->transaction);
    }

    public function init($transaction): void
    {
        $this->airtelTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function addConflict(?string $message): void
    {
        $conflict = new TransactionConflicts();
        $conflict->state = $message;
        $conflict->transaction()->associate($this->airtelTransaction);
        $conflict->save();
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
}