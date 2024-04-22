<?php
namespace Inensus\AirtelPaymentProvider\Providers;

use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Inensus\AirtelPaymentProvider\Services\AirtelTransactionService;
use MPM\Transaction\Provider\ITransactionProvider;
use SimpleXMLElement;

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
        //approve transaction : airtel transactions are automatically confirmed thus
        //only save it as successful
        if ($requestType) {
            $this->airtelTransaction->status = 1;
            $this->airtelTransaction->save();
        } else {
            $this->airtelTransaction->status = -1;
            $this->airtelTransaction->save();
        }
    }

    public function validateRequest($request)
    {
        $transactionXml = new SimpleXMLElement($request);
        $transactionData = json_encode($transactionXml);
        $transactionData = json_decode($transactionData, true);

        $validator = Validator::make($transactionData, [
            'TYPE' => 'required',
            'CUSTOMERMSISDN' => 'required',
            'MERCHANTMSISDN' => 'required',
            'AMOUNT' => 'required',
            'REFERENCE' => 'required',
            'REFERENCE1' => 'required',
        ]);
        if ($validator->fails()) {
            throw  new \Exception("Invalid request");
        }

        $meterSerial = $transactionData['REFERENCE'];
        $amount = $transactionData['AMOUNT'];

        try {
            $this->airtelTransactionService->validatePaymentOwner($meterSerial, $amount);
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

    public function getSubTransaction()
    {
        return $this->airtelTransaction;
    }

    public function confirm(): void
    {
        // TODO: Implement confirm() method.
    }

    public function getMessage(): string
    {
        return $this->airtelTransactionService->getMeterSerialNumber();
    }

    public function getAmount(): int
    {
        return $this->airtelTransactionService->getAmount();
    }

    public function getSender(): string
    {
        return $this->airtelTransactionService->getMeterSerialNumber();
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

    public function notifyCustomerViaSms(): bool
    {
        return $this->notifyCustomerViaSms;
    }

}