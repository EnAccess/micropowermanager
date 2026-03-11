<?php

namespace App\Services\ImportServices;

use App\Models\Device;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionImportService extends AbstractImportService {
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function import(array $data): array {
        $importData = $data;
        if (isset($data['data']) && is_array($data['data'])) {
            $importData = $data['data'];
        }

        $errors = $this->validate($importData);
        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($importData as $transactionData) {
                try {
                    $result = $this->importTransaction($transactionData);
                    if ($result['success']) {
                        $imported[] = $result['transaction'];
                    } else {
                        $failed[] = [
                            'device_id' => $transactionData['device_id'] ?? 'unknown',
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing transaction', [
                        'device_id' => $transactionData['device_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'device_id' => $transactionData['device_id'] ?? 'unknown',
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            return [
                'success' => true,
                'message' => 'Transactions imported successfully',
                'imported_count' => count($imported),
                'failed_count' => count($failed),
                'imported' => $imported,
                'failed' => $failed,
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Error during transaction import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'errors' => ['transaction' => 'Failed to import transactions: '.$e->getMessage()],
            ];
        }
    }

    /**
     * @param array<string, mixed> $transactionData
     *
     * @return array<string, mixed>
     */
    private function importTransaction(array $transactionData): array {
        $deviceSerial = $transactionData['device_id'];

        // Verify device exists
        $device = Device::query()->where('device_serial', $deviceSerial)->first();
        if ($device === null) {
            return [
                'success' => false,
                'errors' => ['device_id' => "Device with serial '{$deviceSerial}' not found"],
            ];
        }

        // Parse amount — export format includes currency symbol and comma separators
        $amount = $transactionData['amount'] ?? 0;
        if (is_string($amount)) {
            $amount = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '', $amount));
        }

        $transaction = Transaction::query()->create([
            'amount' => $amount,
            'sender' => $transactionData['customer'] ?? '',
            'message' => $deviceSerial,
            'type' => 'imported',
            'original_transaction_type' => $transactionData['transaction_type'] ?? null,
            'created_at' => $transactionData['sent_date'] ?? now(),
        ]);

        return [
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'device_serial' => $deviceSerial,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function validate(array $data): array {
        $errors = [];

        foreach ($data as $index => $transactionData) {
            if (!is_array($transactionData)) {
                $errors["transaction_{$index}"] = 'Transaction data must be an array';
                continue;
            }

            if (empty($transactionData['device_id'])) {
                $errors["transaction_{$index}.device_id"] = 'Device serial number is required';
            }

            if (!isset($transactionData['amount'])) {
                $errors["transaction_{$index}.amount"] = 'Amount is required';
            }
        }

        return $errors;
    }
}
