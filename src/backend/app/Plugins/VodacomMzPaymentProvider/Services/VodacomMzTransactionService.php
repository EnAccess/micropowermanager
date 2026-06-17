<?php

namespace App\Plugins\VodacomMzPaymentProvider\Services;

use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use App\Plugins\VodacomMzPaymentProvider\Exceptions\VodacomMzApiResponseException;
use App\Plugins\VodacomMzPaymentProvider\Http\Clients\VodacomMzApiClient;
use App\Plugins\VodacomMzPaymentProvider\Models\VodacomMzTransaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\Interfaces\PaymentInitiator;

/**
 * @extends AbstractPaymentAggregatorTransactionService<VodacomMzTransaction>
 */
class VodacomMzTransactionService extends AbstractPaymentAggregatorTransactionService implements PaymentInitiator {
    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private VodacomMzTransaction $vodacomMzTransaction,
        private VodacomMzApiClient $apiClient,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->vodacomMzTransaction
        );
    }

    /**
     * Validate a transaction request.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function validateTransaction(array $data): array {
        return [];
    }

    /**
     * Process a transaction.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function processTransaction(array $data): array {
        return [];
    }

    /**
     * Check the status of a transaction.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function transactionEnquiryStatus(array $data): array {
        // $referenceId = $data['referenceId'];
        $statuses = ['Failed', 'Succeed', 'Pending'];
        $randomStatus = $statuses[array_rand($statuses)];

        return [
            'status' => [$randomStatus],
        ];
    }

    /**
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
     */
    public function initiatePayment(
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
    ): array {
        $thirdPartyReference = $this->generateThirdPartyReference($serialId);

        // c2bPayment is single-stage and synchronous: IPG only responds after the payer has
        // entered their M-Pesa PIN and the payment has been processed. The transaction is
        // therefore persisted as REQUESTED up front, so a timeout or a rejected push still
        // leaves a record that can be reconciled later via queryTransactionStatus.
        $vodacomMzTransaction = $this->vodacomMzTransaction->newQuery()->create([
            'serialNumber' => $serialId,
            'amount' => $amount,
            'payerPhoneNumber' => $sender,
            'status' => VodacomMzTransaction::STATUS_REQUESTED,
            'referenceId' => $thirdPartyReference,
        ]);

        $transaction = $vodacomMzTransaction->transaction()->create([
            'amount' => $amount,
            'sender' => $sender,
            'message' => $message,
            'type' => $type,
        ]);

        $response = $this->apiClient->c2bPayment(
            (string) $serialId,
            $sender,
            $amount,
            $thirdPartyReference
        );

        $succeeded = ($response['output_ResponseCode'] ?? null) === VodacomMzApiClient::RESPONSE_SUCCESS;

        $vodacomMzTransaction->update([
            'status' => $succeeded ? VodacomMzTransaction::STATUS_SUCCESS : VodacomMzTransaction::STATUS_FAILED,
            'conversationId' => $response['output_ConversationID'] ?? null,
            'transactionId' => $response['output_TransactionID'] ?? null,
        ]);

        if (!$succeeded) {
            throw new VodacomMzApiResponseException('Vodacom MZ c2b push was rejected: '.($response['output_ResponseCode'] ?? 'unknown code').': '.($response['output_ResponseDesc'] ?? 'unknown error'));
        }

        return [
            'transaction' => $transaction,
            'provider_data' => [],
            'process_immediately' => true,
        ];
    }

    /**
     * Builds the unique reference IPG uses to identify this request (input_ThirdPartyReference),
     * which is also how we later reconcile the transaction via queryTransactionStatus.
     *
     * IPG rejects non-alphanumeric characters (including "-" and "_"), so the serial — which may be
     * a UUID containing dashes — is stripped down to alphanumerics and a unix timestamp is appended.
     * The serial keeps it human-mappable; the timestamp keeps it ordered and unique per second.
     *
     * @return string a reference such as "MTR123451718553600"
     */
    private function generateThirdPartyReference(?string $serialNumber): string {
        $cleanSerial = preg_replace('/[^A-Za-z0-9]/', '', $serialNumber ?? 'NA');

        return $cleanSerial.now()->timestamp;
    }
}
