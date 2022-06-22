<?php


namespace Inensus\SwiftaPaymentProvider\Providers;


use App\Jobs\SmsProcessor;
use App\Lib\ITransactionProvider;
use App\Models\Address\Address;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Inensus\SwiftaPaymentProvider\Http\Exceptions\SwiftaValidationFailedException;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\SwiftaPaymentProvider\Services\SwiftaTransactionService;

class SwiftaTransactionProvider implements ITransactionProvider
{
    private $transaction;
    private $swiftaTransaction;
    private $swiftaTransactionService;
    private $validData = [];
    private $address;

    public function __construct(
        Transaction $transaction,
        SwiftaTransaction $swiftaTransaction,
        SwiftaTransactionService $swiftaTransactionService,
        Address $address
    ) {
        $this->swiftaTransaction = $swiftaTransaction;
        $this->transaction = $transaction;
        $this->swiftaTransactionService = $swiftaTransactionService;
        $this->address = $address;
    }

    public function validateRequest($request)
    {
        $this->validData = array_merge($this->validData, $request->all());
        try {
            $this->swiftaTransactionService->validateInComingTransaction($this->validData);
        }catch (\Exception $exception){
          throw  new \Exception($exception->getMessage());
        }

    }

    public function saveTransaction()
    {
        $this->swiftaTransaction = $this->swiftaTransactionService->assignIncomingDataToSwiftaTransaction($this->validData);
        $this->transaction = $this->swiftaTransactionService->assignIncomingDataToTransaction($this->validData);
    }

    public function sendResult(bool $requestType, Transaction $transaction)
    {
        $this->swiftaTransaction = $transaction->originalTransaction()->first();
        if ($requestType) {
            $this->swiftaTransaction->status = 1;
            $this->swiftaTransaction->save();
            SmsProcessor::dispatch(
                $transaction,
                SmsTypes::TRANSACTION_CONFIRMATION,
                SmsConfigs::class
            )->allOnConnection('redis')->onQueue(\config('services.queues.sms'));
        } else {
            Log::debug('swifta transaction is been cancelled',);
            $this->swiftaTransaction->status = -1;
            $this->swiftaTransaction->save();
        }
    }

    public function confirm(): void
    {
        echo $xmlResponse =
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<Response>' .
            '<TYPE>MESOMB PAYMENT</TYPE>' .
            '<REFERENCE>' . $this->swiftaTransaction->transaction_reference . '</REFERENCE>' . // the PK from original request
            '<TXNSTATUS>$this->swiftaTransaction->status</TXNSTATUS>' .
            '<AMOUNT>' . $this->swiftaTransaction->amount . '</AMOUNT>' .
            '<CIPHER>' . $this->swiftaTransaction->cipher . '</CIPHER>' .
            '<TIMESTAMB>' . $this->swiftaTransaction->timestamp . '</TIMESTAMB>' .
            '</Response>';
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

    public function saveCommonData(): Model
    {
        return $this->swiftaTransactionService->associateSwiftaTransactionWithTransaction($this->swiftaTransaction,
            $this->transaction);
    }

    public function init($transaction): void
    {
        // TODO: Implement init() method.
    }

    public function conflict(?string $message,$transaction): void
    {
        $this->swiftaTransaction = $transaction->originalTransaction()->first();
        $conflict = new TransactionConflicts();
        $conflict->state = $message;
        $conflict->transaction()->associate($this->swiftaTransaction);
        $conflict->save();
    }
    public function addConflict(?string $message): void
    {
        // TODO: Implement getTransaction() method.
    }
    public function getTransaction(): Transaction
    {
        // TODO: Implement getTransaction() method.
    }

    public function setValidData($request)
    {
        $this->validData = array_merge($this->validData, $request->all());
    }
}