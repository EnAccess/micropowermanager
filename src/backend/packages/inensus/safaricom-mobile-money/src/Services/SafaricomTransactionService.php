<?php

namespace Inensus\SafaricomMobileMoney\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inensus\SafaricomMobileMoney\Enums\TransactionStatus;
use Inensus\SafaricomMobileMoney\Models\SafaricomSettings;
use Inensus\SafaricomMobileMoney\Models\SafaricomTransaction;

class SafaricomTransactionService {
    public function __construct(
        private SafaricomSettings $settings,
        private SafaricomAuthService $authService,
    ) {}

    /**
     * Initiate STK Push for payment.
     *
     * @param array $data
     *
     * @return array
     *
     * @throws \Exception
     */
    public function initiateSTKPush(array $data): array {
        $settings = $this->settings->query()->first();
        if (!$settings) {
            throw new \Exception('Safaricom settings not configured');
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($settings->shortcode.$settings->passkey.$timestamp);

        $referenceId = Str::uuid()->toString();

        // Create transaction record
        $transaction = SafaricomTransaction::create([
            'reference_id' => $referenceId,
            'amount' => $data['amount'],
            'phone_number' => $data['phone_number'],
            'account_reference' => $data['account_reference'] ?? null,
            'transaction_desc' => $data['transaction_desc'] ?? 'Payment',
            'status' => TransactionStatus::INITIATED,
        ]);

        $payload = [
            'BusinessShortCode' => $settings->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $data['amount'],
            'PartyA' => $data['phone_number'],
            'PartyB' => $settings->shortcode,
            'PhoneNumber' => $data['phone_number'],
            'CallBackURL' => config('safaricom-mobile-money.webhook.result_url'),
            'AccountReference' => $data['account_reference'] ?? $referenceId,
            'TransactionDesc' => $data['transaction_desc'] ?? 'Payment',
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->authService->getAccessToken(),
            'Content-Type' => 'application/json',
        ])->post(config('safaricom-mobile-money.api.base_url').'/mpesa/stkpush/v1/processrequest', $payload);

        if (!$response->successful()) {
            $transaction->update(['status' => TransactionStatus::FAILED]);
            throw new \Exception('Failed to initiate STK Push: '.$response->body());
        }

        $responseData = $response->json();
        $transaction->update([
            'status' => TransactionStatus::PENDING,
            'response_data' => $responseData,
        ]);

        return [
            'reference_id' => $referenceId,
            'checkout_request_id' => $responseData['CheckoutRequestID'] ?? null,
            'status' => TransactionStatus::PENDING->value,
        ];
    }

    /**
     * Check transaction status.
     *
     * @param string $referenceId
     *
     * @return array
     */
    public function checkTransactionStatus(string $referenceId): array {
        $transaction = SafaricomTransaction::where('reference_id', $referenceId)->first();

        if (!$transaction) {
            throw new \Exception('Transaction not found');
        }

        return [
            'reference_id' => $transaction->reference_id,
            'amount' => $transaction->amount,
            'phone_number' => $transaction->phone_number,
            'status' => $transaction->status->value,
            'mpesa_receipt_number' => $transaction->mpesa_receipt_number,
            'transaction_date' => $transaction->transaction_date,
        ];
    }

    /**
     * Update transaction status from webhook.
     *
     * @param array $data
     *
     * @return void
     */
    public function updateTransactionStatus(array $data): void {
        $transaction = SafaricomTransaction::where('reference_id', $data['AccountReference'])->first();

        if (!$transaction) {
            throw new \Exception('Transaction not found');
        }

        $status = match ($data['ResultCode']) {
            0 => TransactionStatus::SUCCEEDED,
            default => TransactionStatus::FAILED,
        };

        $transaction->update([
            'status' => $status,
            'mpesa_receipt_number' => $data['MpesaReceiptNumber'] ?? null,
            'transaction_date' => $data['TransactionDate'] ?? null,
            'response_data' => $data,
        ]);
    }
}
