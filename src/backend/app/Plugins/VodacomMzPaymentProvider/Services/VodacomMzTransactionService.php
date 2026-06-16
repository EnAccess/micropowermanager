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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * @return array{transaction: Transaction, provider_data: array<string, mixed>}
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

        try {
            DB::connection('tenant')->beginTransaction();

            $vodacomMzTransaction = $this->vodacomMzTransaction->newQuery()->create([
                'serialNumber' => $serialId,
                'amount' => $amount,
                'payerPhoneNumber' => $sender,
                'status' => VodacomMzTransaction::STATUS_REQUESTED,
                'referenceId' => $thirdPartyReference,
            ]);

            /** @var Transaction $transaction */
            $transaction = $vodacomMzTransaction->transaction()->create([
                'amount' => $amount,
                'sender' => $sender,
                'message' => $message,
                'type' => $type,
            ]);

            // Pushes a PIN prompt to the payer's handset; the customer confirms with
            // their M-Pesa PIN. The final outcome arrives asynchronously and is
            // reconciled via queryTransactionStatus, so we only assert the push was
            // accepted here and leave the transaction in REQUESTED status.
            $response = $this->apiClient->c2bPayment(
                (string) $serialId,
                $sender,
                $amount,
                $thirdPartyReference
            );

            if (($response['output_ResponseCode'] ?? null) !== VodacomMzApiClient::RESPONSE_SUCCESS) {
                throw new VodacomMzApiResponseException('Vodacom MZ c2b push was rejected: '.($response['output_ResponseCode'] ?? 'unknown code').': '.($response['output_ResponseDesc'] ?? 'unknown error'));
            }

            DB::connection('tenant')->commit();
        } catch (\Throwable $exception) {
            DB::connection('tenant')->rollBack();
            throw $exception;
        }

        return [
            'transaction' => $transaction,
            'provider_data' => [],
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
