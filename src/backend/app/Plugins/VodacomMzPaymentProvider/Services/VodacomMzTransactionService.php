<?php

namespace App\Plugins\VodacomMzPaymentProvider\Services;

use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use App\Plugins\VodacomMzPaymentProvider\Models\VodacomMzTransaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\Interfaces\IBaseService;

class VodacomMzTransactionService extends AbstractPaymentAggregatorTransactionService {
    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private VodacomMzTransaction $vodacomMzTransaction,
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

    public function initializePayment(
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
    ): array {
        // dd('Here we are!');
        return [
            // 'transaction' => $transaction,
            // 'provider_data' => [
            //     'redirect_url' => $result['redirectionUrl'],
            //     'reference' => $result['reference'],
            // ],
        ];
    }
}
