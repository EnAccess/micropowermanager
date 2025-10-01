<?php

namespace Inensus\VodacomMobileMoney\Services;

class VodacomTransactionService {
    /**
     * Validate a transaction request.
     */
    public function validateTransaction(array $data): array {
        return [];
    }

    /**
     * Process a transaction.
     */
    public function processTransaction(array $data): array {
        return [];
    }

    /**
     * Check the status of a transaction.
     */
    public function transactionEnquiryStatus(array $data): array {
        // $referenceId = $data['referenceId'];
        $statuses = ['Failed', 'Succeed', 'Pending'];
        $randomStatus = $statuses[array_rand($statuses)];

        return [
            'status' => [$randomStatus],
        ];
    }
}
