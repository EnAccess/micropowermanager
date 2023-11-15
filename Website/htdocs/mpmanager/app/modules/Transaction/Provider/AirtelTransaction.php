<?php
namespace MPM\Transaction\Provider;

use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SimpleXMLElement;

class AirtelTransaction implements ITransactionProvider
{
    /**
     * @var \App\Models\Transaction\AirtelTransaction
     */
    private $airtelTransaction;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * contains validated data
     *
     * @var SimpleXMLElement
     */
    private $validData;

    /**
     * DI will initialize the needed models
     *
     * @param \App\Models\Transaction\AirtelTransaction $airtelTransaction
     * @param Transaction                               $transaction
     */
    public function __construct(\App\Models\Transaction\AirtelTransaction $airtelTransaction, Transaction $transaction)
    {
        $this->airtelTransaction = $airtelTransaction;
        $this->transaction = $transaction;
    }


    public function saveTransaction(): void
    {
        $this->airtelTransaction = new \App\Models\Transaction\AirtelTransaction();
        $this->transaction = new Transaction();
        //assign data
        $this->assignData($this->validData);

        //save transaction
        $this->saveData($this->airtelTransaction);
    }

    /**
     * @param bool        $requestType
     * @param Transaction $transaction
     */
    public function sendResult(bool $requestType, Transaction $transaction): void
    {

        //approve transaction : airtel transactions are automatically confirmed thus
        //only save it as successful
        if ($requestType) {
            $this->airtelTransaction->status = 1;
            $this->airtelTransaction->save();
            $smsService = app()->make(SmsService::class);
            $smsService->sendSms($transaction, SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
        } else { //send cancellation to airtel gateway server and this will send the final request to airtel
            $requestContent = $this->prepareRequest($this->transaction, $this->airtelTransaction);

            $client = new Client();
            $response = $client->post(
                config('services.airtel.request_url'),
                [
                    'headers' => [
                        'Content-Type: tex/xml',
                        'Connection: Keep-Alive',
                    ],
                    'body' => $requestContent,
                ]
            );
            Log::critical(
                'airtel transaction is been cancelled',
                [
                'code' => $response->getStatusCode(),
                'response' => $response->getBody(),
                ]
            );
            $this->airtelTransaction->status = -1;
            $this->airtelTransaction->save();
        }
    }

    public function validateRequest($request): bool
    {
        $transactionXml = new SimpleXMLElement($request);
        $transactionData = json_encode($transactionXml);
        $transactionData = json_decode($transactionData, true);

        $validator = Validator::make(
            $transactionData,
            [
            'APIUser' => 'required|in:' . config('services.airtel.api_user'),
            'APIPassword' => 'required|in:' . config('services.airtel.api_password'),
            'INTERFACEID' => 'required',
            'BusinessNumber' => 'required',
            'TransID' => 'required',
            'Amount' => 'required',
            'ReferenceField' => 'required',
            'Msisdn' => 'required',
            'TRID' => 'required',
            ]
        );
        if ($validator->fails()) {
            dd($validator->errors());
            return false;
        }
        $this->validData = $transactionXml;
        return true;
    }

    public function confirm(): void
    {
        echo $xmlResponse =
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<Response>' .
            '<TYPE>CMPRRES</TYPE>' .
            '<TXNID>' . $this->airtelTransaction->id . '</TXNID>' . // the TransID from original request
            '<TXNSTATUS>200</TXNSTATUS>' .
            '<ReferenceField>' . $this->transaction->message . '</ReferenceField>' .
            '<MESSAGE>' . $this->transaction->message . '</MESSAGE>' .
            '<TransID>' . $this->airtelTransaction->id . '</TransID>' .
            '<Msisdn>' . $this->airtelTransaction->id . '</Msisdn>' . // is not been processed by airtel.
            '</Response>';
    }

    public function getMessage(): string
    {
        return $this->transaction->message;
    }

    public function getAmount(): int
    {
        return $this->transaction->amount;
    }

    public function getSender(): string
    {
        return $this->transaction->sender;
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


    private function assignData(SimpleXMLElement $data): void
    {
        //provider specific data
        $this->airtelTransaction->interface_id = (string)$data->INTERFACEID;
        $this->airtelTransaction->business_number = (string)$data->BusinessNumber;
        $this->airtelTransaction->trans_id = (string)$data->TransID;
        $this->airtelTransaction->tr_id = (string)$data->TRID;
        // common transaction data
        $this->transaction->amount = (int)$data->Amount;
        $this->transaction->sender = '255' . (string)$data->Msisdn;
        $this->transaction->message = $data->ReferenceField;
    }

    /**
     * Saves the airtel transaction
     *
     * @param \App\Models\Transaction\AirtelTransaction $airtelTransaction
     */
    public function saveData(\App\Models\Transaction\AirtelTransaction $airtelTransaction): void
    {
        $airtelTransaction->save();
        event('transaction.confirm');
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

    /**
     * Prepares the data for either a confirmation- or a cancellation-request
     *
     * @param  $transaction
     * @param \App\Models\Transaction\AirtelTransaction $airtelTransaction
     * @return string
     */
    private function prepareRequest(
        Transaction $transaction,
        \App\Models\Transaction\AirtelTransaction $airtelTransaction
    ): string {
        return '<COMMAND>' .
            '<TYPE>ROLLBACK</TYPE>' .
            '<TXNID>' . $airtelTransaction->trans_id . '</TXNID>' .
            '<TRID>' . $airtelTransaction->id . '</TRID>' .
            '<MESSAGE>Transaction is cancelled</MESSAGE>' .
            '</COMMAND>';
    }
}
