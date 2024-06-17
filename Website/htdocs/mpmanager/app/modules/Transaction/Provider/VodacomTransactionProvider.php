<?php

namespace MPM\Transaction\Provider;

use App\Exceptions\VodacomHeartBeatException;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Models\Transaction\VodacomTransaction;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VodacomTransactionProvider implements ITransactionProvider
{
    private VodacomTransaction $vodacomTransaction;
    private Transaction $transaction;
    private \SimpleXMLElement $valid_data;

    /**
     * Is responsible for the whole incoming Transaction process.
     */
    public function saveTransaction(): void
    {
        // initializes needed model
        $this->vodacomTransaction = new VodacomTransaction();

        $this->transaction = new Transaction();

        // assign data
        $this->assignData($this->valid_data);
        // save transaction
        $this->saveData($this->vodacomTransaction);
    }

    /**
     * Sends the final state of the transaction based on resultType.
     *
     * @param bool        $requestType
     * @param Transaction $transaction
     */
    public function sendResult(bool $requestType, Transaction $transaction): void
    {
        if (!app()->environment('production')) {
            return;
        }
        $requestContent = $this->prepareRequest($requestType);

        $request = new Client();

        try {
            $response = $request->post(
                \config('services.vodacom.request_url'),
                [
                    'headers' => [
                        'Content-Type: tex/xml',
                        'Connection: Keep-Alive',
                    ],
                    'body' => $requestContent,
                    'cert' => \config('services.vodacom.certs.ssl_cert'),
                    'ssl_key' => \config('services.vodacom.certs.ssl_key'),
                ]
            );
            // request was  successful
            if ($response->getStatusCode() === 200) {
                if (!$requestType) {
                    Log::critical(
                        'Vodacom transaction cancelled',
                        [
                            'id' => '64324632786d78638762387',
                            'response' => $response->getBody(),
                            'body' => $requestContent,
                        ]
                    );
                } else {
                    $smsService = app()->make(SmsService::class);
                    $smsService->sendSms($transaction, SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
                }

                // make response xml object
                try {
                    $xmlResponse = new \SimpleXMLElement($response->getBody());
                    // vodacom returns 0 for confirmation and 999 for cancellation request
                    // cast bool to int gives (0,1) as number. Multiply its inverse by
                    // 999 gives 0 for true and 999 for false
                    $expectedResponseCode = (int) !$requestType * 999;
                    if ((int) $xmlResponse->response->responseCode === $expectedResponseCode) {
                        // mark transaction as done
                        $this->vodacomTransaction->status = $requestType === true ? 1 : -1;
                        $this->vodacomTransaction->save();
                    } else {
                        $this->addConflict('Transaction got '.$response->getStatusCode().' as HTTP Status code '
                            .$response->getBody());
                    }
                } catch (\Exception $e) {
                    Log::critical(
                        'Vodacom transaction Finalize',
                        [
                            'id' => '5435h4u393489',
                            'response' => $response->getBody(),
                            'error' => $e->getMessage(),
                            'sent' => $requestContent,
                        ]
                    );
                }
            } else {
                $this->addConflict('Transaction got '.$response->getStatusCode().' as HTTP Status code');
            }

            return;
        } catch (\Exception $e) {
            $this->addConflict($e->getMessage());
            Log::critical(
                'Failed to send final transaction request',
                ['id' => '48285968fu3048', 'message' => $e->getMessage()]
            );
        }
    }

    /**
     * Validates incoming request
     * stores the request data to valid_data if  request is valid.
     *
     * @param $request
     *
     * @throws \Exception
     */
    public function validateRequest($request): void
    {
        if ($request === '<FailSafe>JUMEME</FailSafe>') {
            throw new VodacomHeartBeatException('heartbeat');
        }
        $transaction_xml = new \SimpleXMLElement($request);
        $transaction_data = json_encode($transaction_xml);
        $transaction_data = json_decode($transaction_data, true);

        $validator = Validator::make(
            $transaction_data,
            [
                'request.serviceProvider.spId' => 'required|in:'.Config::get('services.vodacom.sp_id'),
                'request.serviceProvider.spPassword' => 'required',
                'request.transaction.amount' => 'required',
                'request.transaction.commandID' => 'required',
                'request.transaction.initiator' => 'required',
                'request.transaction.conversationID' => 'required|unique:vodacom_transactions,conversation_id',
                'request.transaction.originatorConversationID' => 'required|unique:vodacom_transactions,originator_conversation_id',
                'request.transaction.recipient' => 'required',
                'request.transaction.mpesaReceipt' => 'required',
                'request.transaction.transactionDate' => 'required',
                'request.transaction.accountReference' => 'required|min:3',
                'request.transaction.transactionID' => 'required',
            ]
        );
        if ($validator->fails()) {
            throw new \Exception($validator->errors());
        }
        $this->valid_data = $transaction_xml;
    }

    /**
     * Send a confirmation (output for incoming api request) that the transaction passed validation and will be
     * processed.
     */
    public function confirm(): void
    {
        echo $xmlResponse =
            '<?xml version="1.0" encoding="UTF-8"?>'.
            '<mpesaBroker xmlns="http://infowise.co.tz/broker/" version="2.0">'.
            '<response>'.
            '<conversationID>'.$this->vodacomTransaction->conversation_id.'</conversationID>'.
            '<originatorConversationID>'.$this->vodacomTransaction->originator_conversation_id.
            '</originatorConversationID>'.
            '<transactionID>'.$this->vodacomTransaction->transaction_id.'</transactionID>'.
            '<responseCode>0</responseCode>'.
            '<responseDesc>Received</responseDesc>'.
            '<serviceStatus>Success</serviceStatus>'.
            '</response>'.
            '</mpesaBroker>';
    }

    /**
     * The message which is been typed by the user.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->transaction->message;
    }

    /**
     * The amount sent by the user.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->transaction->amount;
    }

    /**
     * The number, which sent money.
     *
     * @return string
     */
    public function getSender(): string
    {
        return $this->transaction->sender;
    }

    /**
     * Store the commonly used data.
     *
     * @return Transaction
     */
    public function saveCommonData(): Model
    {
        return $this->vodacomTransaction->transaction()->save($this->transaction);
    }

    /**
     * Assign request data to model.
     *
     * @param $data
     */
    private function assignData(\SimpleXMLElement $data): void
    {
        $transaction_data = $data->request->transaction;
        $this->vodacomTransaction->conversation_id = (string) $transaction_data->conversationID;
        $this->vodacomTransaction->originator_conversation_id = (string) $transaction_data->originatorConversationID;
        $this->vodacomTransaction->mpesa_receipt = (string) $transaction_data->mpesaReceipt;
        $this->vodacomTransaction->transaction_date = (string) $transaction_data->transactionDate;
        $this->vodacomTransaction->transaction_id = (string) $transaction_data->transactionID;

        $this->transaction->amount = (int) $transaction_data->amount;
        $this->transaction->sender = (string) $transaction_data->initiator;
        $this->transaction->message = (string) $transaction_data->accountReference;
    }

    public function saveData(VodacomTransaction $vodacomTransaction): void
    {
        $vodacomTransaction->save();
        // confirm transaction with
        event('transaction.confirm', $this);
    }

    private function prepareRequest(bool $requestType): string
    {
        $time = date('YmdHis');
        $providerPassword = $this->encryptProviderPassword($time);
        $phonePassword = $this->encryptPhonePassword();

        if (!$requestType) {
            $requestContent = $this->serveResult(
                $providerPassword,
                $phonePassword,
                $time,
                'Failure',
                999
            );
        } else {
            $requestContent = $this->serveResult(
                $providerPassword,
                $phonePassword,
                $time,
                'Success',
                0
            );
        }

        return $requestContent;
    }

    /**
     * Generates result for cancel/approve transaction.
     *
     * @param string $serviceProviderPassword
     * @param string $phonePassword
     * @param string $time                    Y-md H:i:s
     * @param string $result
     * @param int    $resultCode
     *
     * @return string
     */
    private function serveResult(
        $serviceProviderPassword,
        $phonePassword,
        $time,
        $result = 'Failure',
        $resultCode = 999
    ): string {
        return
            '<?xml version="1.0" encoding="UTF-8"?>
        <mpesaBroker xmlns="http://infowise.co.tz/broker/" version="2.0">
        <result>
            <serviceProvider>
                <spId>'.\config()->get('services.vodacom.sp_id').'</spId>
                <spPassword>'.$serviceProviderPassword.'</spPassword>
                <timestamp>'.$time.'</timestamp>
            </serviceProvider>
            <transaction>
                <resultType>Completed</resultType>
                <resultCode>'.$resultCode.'</resultCode>
                <resultDesc>'.$result.' </resultDesc>
                <serviceReceipt>'.$this->transaction->id.'</serviceReceipt>
                <serviceDate>'.date('Y-m-d H:i:s').'</serviceDate>
                <serviceID>'.$this->transaction->id.'</serviceID>
                <originatorConversationID>'.$this->vodacomTransaction->originator_conversation_id.
                '</originatorConversationID>
                <conversationID>'.$this->vodacomTransaction->conversation_id.'</conversationID>
                <transactionID>'.$this->vodacomTransaction->transaction_id.'</transactionID>
                <initiator>'.$this->transaction->sender.'</initiator>
                <initiatorPassword>'.$phonePassword.'</initiatorPassword>
            </transaction >
        </result >
        </mpesaBroker >';
    }

    private function encryptProviderPassword(string $time): string
    {
        $password = strtoupper(
            hash(
                'sha256',
                sprintf(
                    '%s%s%s',
                    \config()->get('services.vodacom.sp_id'),
                    \config()->get('services.vodacom.sp_password'),
                    $time
                ),
                true
            )
        );

        return base64_encode($password);
    }

    /**
     * @return string
     */
    private function encryptPhonePassword()
    {
        $keyFile = fopen(\config()->get('services.vodacom.certs.broker'), 'rb');
        $publicKey = fread($keyFile, 8192);
        fclose($keyFile);
        openssl_public_encrypt($this->transaction->sender, $encrypted, $publicKey);

        return base64_encode($encrypted);
    }

    public function init($transaction): void
    {
        $this->vodacomTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function addConflict(?string $message): void
    {
        $conflict = new TransactionConflicts();
        $conflict->state = $message;
        $conflict->transaction()->associate($this->vodacomTransaction);
        $conflict->save();
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
}
