<?php

namespace Inensus\MesombPaymentProvider\Providers;

use App\Models\Address\Address;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inensus\MesombPaymentProvider\Exceptions\MesombPayerMustHaveOnlyOneConnectedMeterException;
use Inensus\MesombPaymentProvider\Exceptions\MesombPaymentPhoneNumberNotFoundException;
use Inensus\MesombPaymentProvider\Exceptions\MesombStatusFailedException;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\MesombPaymentProvider\Services\MesomTransactionService;
use MPM\Transaction\Provider\ITransactionProvider;

class MesombTransactionProvider implements ITransactionProvider {
    /** @var array<string, mixed> */
    private array $validData = [];

    public function __construct(private Transaction $transaction, private MesombTransaction $mesombTransaction, private MesomTransactionService $mesombTransactionService, private Address $address) {}

    public function validateRequest(Request $request): void {
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

    public function saveCommonData(): Transaction {
        return $this->mesombTransactionService->associateMesombTransactionWithTransaction(
            $this->mesombTransaction,
            $this->transaction
        );
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        $mesombTransaction = $transaction->originalTransaction;
        if (!$mesombTransaction instanceof MesombTransaction) {
            throw new \Exception('Wrong transaction type.');
        }
        $this->mesombTransaction = $mesombTransaction;

        if ($requestType) {
            $this->mesombTransaction->status = 1;
            $this->mesombTransaction->save();
            $smsService = app()->make(SmsService::class);
            $smsService->sendSms($transaction->toArray(), SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
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

    /**
     * @param array<string, mixed> $requestData
     */
    private function checkPhoneIsExists(array $requestData): ?Address {
        $personAddresses = $this->address->newQuery()
            ->where('phone', $requestData['b_party'])
            ->orWhere('phone', '+'.$requestData['b_party'])->get();
        $phoneNumbersCount = $personAddresses->count();
        if ($phoneNumbersCount > 1 || $phoneNumbersCount == 0) {
            throw new MesombPaymentPhoneNumberNotFoundException('Each payer must have if and only if registered phone number. Registered phone count with '.$requestData['b_party'].'is '.$phoneNumbersCount);
        }

        return $personAddresses->first();
    }

    private function checkSenderHasOnlyOneMeterRegistered(Address $senderAddress): void {
        /** @var Person|null $senderPerson */
        $senderPerson = $senderAddress->newQuery()->whereHasMorph(
            'owner',
            [Person::class]
        )->first()?->owner;

        $senderMeters = $senderPerson->devices;
        $senderMetersCount = $senderMeters->count();
        if ($senderMetersCount > 1 || $senderMetersCount == 0) {
            throw new MesombPayerMustHaveOnlyOneConnectedMeterException('Each payer must have if and only if connected meter with one phone number. Registered meter count is '.$senderMetersCount);
        }

        $this->validData['meter'] = $senderMeters->first()->device()->first()->serial_number;
    }

    /**
     * @param MesombTransaction $transaction
     */
    public function init($transaction): void {
        // TODO: Implement init() method.
    }

    public function addConflict(?string $message): void {
        // TODO: Implement addConflict() method.
    }

    public function getTransaction(): Transaction {
        // TODO: Implement getTransaction() method.
        throw new \BadMethodCallException('Method getTransaction() not yet implemented.');
    }

    public function getMessage(): string {
        // TODO: Implement getMessage() method.
        throw new \BadMethodCallException('Method getMessage() not yet implemented.');
    }

    public function getAmount(): float {
        // TODO: Implement getAmount() method.
        throw new \BadMethodCallException('Method getAmount() not yet implemented.');
    }

    public function getSender(): string {
        // TODO: Implement getSender() method.
        throw new \BadMethodCallException('Method getSender() not yet implemented.');
    }
}
