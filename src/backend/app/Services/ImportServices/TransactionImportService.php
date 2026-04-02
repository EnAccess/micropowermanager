<?php

namespace App\Services\ImportServices;

use App\Models\Device;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\MesombPaymentProvider\Models\MesombTransaction;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use App\Plugins\WavecomPaymentProvider\Models\WaveComTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

            $allFailed = count($imported) === 0 && count($failed) > 0;
            $partitioned = $this->partitionResults($imported);

            return [
                'success' => !$allFailed,
                'message' => $allFailed ? 'All transaction imports failed' : 'Transactions imported successfully',
                'imported_count' => count($imported),
                'added_count' => $partitioned['added_count'],
                'modified_count' => $partitioned['modified_count'],
                'failed_count' => count($failed),
                'added' => $partitioned['added'],
                'modified' => $partitioned['modified'],
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

        $sentDate = $transactionData['sent_date'] ?? now();

        // Check for duplicate transaction
        $existingTransaction = Transaction::query()
            ->where('message', $deviceSerial)
            ->where('amount', $amount)
            ->where('created_at', $sentDate)
            ->first();

        if ($existingTransaction !== null) {
            return [
                'success' => true,
                'action' => 'modified',
                'transaction' => [
                    'id' => $existingTransaction->id,
                    'amount' => $existingTransaction->amount,
                    'device_serial' => $deviceSerial,
                    'action' => 'modified',
                ],
            ];
        }

        // Create the provider-specific transaction record matching the original type
        $transactionType = $transactionData['transaction_type'] ?? ThirdPartyTransaction::RELATION_NAME;
        $originalData = $transactionData['original_transaction'] ?? [];
        $originalTransaction = $this->createOriginalTransaction($transactionType, $originalData);

        $transaction = Transaction::query()->create([
            'amount' => $amount,
            'sender' => $transactionData['customer'] ?? '',
            'message' => $deviceSerial,
            'type' => Transaction::TYPE_IMPORTED,
            'original_transaction_id' => $originalTransaction->getKey(),
            'original_transaction_type' => $transactionType,
            'created_at' => $sentDate,
        ]);

        return [
            'success' => true,
            'action' => 'added',
            'transaction' => [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'device_serial' => $deviceSerial,
                'action' => 'added',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $originalData
     */
    private function createOriginalTransaction(string $type, array $originalData): BasePaymentProviderTransaction {
        $modelClass = $this->resolveTransactionModel($type);

        // If no exported data, default to third party transaction
        if ($originalData === []) {
            $modelClass = ThirdPartyTransaction::class;
            $originalData = [
                'transaction_id' => Str::uuid()->toString(),
                'status' => 1,
            ];
        }

        return $modelClass::query()->create($originalData);
    }

    /**
     * @return class-string<BasePaymentProviderTransaction>
     */
    private function resolveTransactionModel(string $type): string {
        return match ($type) {
            CashTransaction::RELATION_NAME => CashTransaction::class,
            AgentTransaction::RELATION_NAME => AgentTransaction::class,
            ThirdPartyTransaction::RELATION_NAME => ThirdPartyTransaction::class,
            SwiftaTransaction::RELATION_NAME => SwiftaTransaction::class,
            MesombTransaction::RELATION_NAME => MesombTransaction::class,
            WaveComTransaction::RELATION_NAME => WaveComTransaction::class,
            WaveMoneyTransaction::RELATION_NAME => WaveMoneyTransaction::class,
            PaystackTransaction::RELATION_NAME => PaystackTransaction::class,
            default => ThirdPartyTransaction::class,
        };
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
