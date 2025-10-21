<?php

namespace Inensus\VodacomMobileMoney\Services;

class VodacomTransactionService {
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
}
