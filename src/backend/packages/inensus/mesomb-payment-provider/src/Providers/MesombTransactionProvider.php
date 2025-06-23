<?php

namespace Inensus\MesombPaymentProvider\Providers;

use App\Models\Address\Address;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Inensus\MesombPaymentProvider\Exceptions\MesombPayerMustHaveOnlyOneConnectedMeterException;
use Inensus\MesombPaymentProvider\Exceptions\MesombPaymentPhoneNumberNotFoundException;
use Inensus\MesombPaymentProvider\Exceptions\MesombStatusFailedException;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\MesombPaymentProvider\Services\MesomTransactionService;
use MPM\Transaction\Provider\ITransactionProvider;

class MesombTransactionProvider implements ITransactionProvider {
    private $transaction;
    private $mesombTransaction;
    private $mesombTransactionService;
    private $validData = [];
    private $address;

    public function __construct(
        Transaction $transaction,
        MesombTransaction $mesombTransaction,
        MesomTransactionService $mesombTransactionService,
        Address $address,
    ) {
        $this->mesombTransaction = $mesombTransaction;
        $this->transaction = $transaction;
        $this->mesombTransactionService = $mesombTransactionService;
        $this->address = $address;
    }

    public function validateRequest($request): void {
        $requestData = $request->all();
        if ($requestData['status'] === 'FAILED') {
            throw new MesombStatusFailedException($requestData['status'].' Sender: '.$requestData['b_party']);
        }

        $senderAddress = $this->checkPhoneIsExists($requestData);
        $this->checkSenderHasOnlyOneMeterRegistered($senderAddress);
        $this->validData = array_merge($this->validData, $requestData);
    }

    public function saveTransaction(): void {
        $this->mesombTransaction = $this->mesombTransactionService->assignIncomingDataToMesombTransaction($this->validData);
        $this->transaction = $this->mesombTransactionService->assignIncomingDataToTransaction($this->validData);
    }

    public function saveCommonData(): Model {
        return $this->mesombTransactionService->associateMesombTransactionWithTransaction(
            $this->mesombTransaction,
            $this->transaction
        );
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        $this->mesombTransaction = $transaction->originalTransaction()->first();
        if ($requestType) {
            $this->mesombTransaction->status = 1;
            $this->mesombTransaction->save();
            $smsService = app()->make(SmsService::class);
            $smsService->sendSms($transaction, SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
        } else {
            Log::critical('mesomb transaction is been cancelled');
            $this->mesombTransaction->status = -1;
            $this->mesombTransaction->save();
        }
    }

    public function confirm(): void {
        echo $xmlResponse =
            '<?xml version="1.0" encoding="UTF-8"?>'.
            '<Response>'.
            '<TYPE>MESOMB PAYMENT</TYPE>'.
            '<TXNPK>'.$this->mesombTransaction->pk.'</TXNPK>'. // the PK from original request
            '<TXNSTATUS>$this->mesombTransaction->status</TXNSTATUS>'.
            '<Sender>'.$this->mesombTransaction->b_party.'</Sender>'.
            '<MESSAGE>'.$this->mesombTransaction->message.'</MESSAGE>'.
            '<TransID>'.$this->mesombTransaction->id.'</TransID>'.
            '<Amount>'.$this->mesombTransaction->amount.'</Amount>'.
            '</Response>';
    }

    private function checkPhoneIsExists($requestData): Model {
        $personAddresses = $this->address->newQuery()
            ->where('phone', $requestData['b_party'])
            ->orWhere('phone', '+'.$requestData['b_party'])->get();
        $phoneNumbersCount = $personAddresses->count();
        if ($phoneNumbersCount > 1 || $phoneNumbersCount == 0) {
            throw new MesombPaymentPhoneNumberNotFoundException('Each payer must have if and only if registered phone number. Registered phone count with '.$requestData['b_party'].'is '.$phoneNumbersCount);
        }

        return $personAddresses->first();
    }

    private function checkSenderHasOnlyOneMeterRegistered($senderAddress): void {
        $senderMeters = $senderAddress->newQuery()->whereHasMorph(
            'owner',
            [Person::class]
        )
            ->first()->owner()->first()->meters();
        $senderMetersCount = $senderMeters->count();
        if ($senderMetersCount > 1 || $senderMetersCount == 0) {
            throw new MesombPayerMustHaveOnlyOneConnectedMeterException('Each payer must have if and only if connected meter with one phone number. Registered meter count is '.$senderMetersCount);
        }

        $this->validData['meter'] = $senderMeters->first()->meter()->first()->serial_number;
    }

    public function init($transaction): void {
        // TODO: Implement init() method.
    }

    public function addConflict(?string $message): void {
        // TODO: Implement addConflict() method.
    }

    public function getTransaction(): Transaction {
        // TODO: Implement getTransaction() method.
    }

    public function getMessage(): string {
        // TODO: Implement getMessage() method.
    }

    public function getAmount(): int {
        // TODO: Implement getAmount() method.
    }

    public function getSender(): string {
        // TODO: Implement getSender() method.
    }
}
