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

/**
 * @extends AbstractImportService<TransactionImportItem>
 */
class TransactionImportService extends AbstractImportService {
    /**
     * @param list<TransactionImportItem> $data
     */
    public function import(array $data): ImportResult {
        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($data as $item) {
                try {
                    $result = $this->importTransaction($item);
                    if ($result['success']) {
                        $imported[] = $result['transaction'];
                    } else {
                        $failed[] = [
                            'device_id' => $item->deviceId,
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing transaction', [
                        'device_id' => $item->deviceId,
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'device_id' => $item->deviceId,
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            $allFailed = count($imported) === 0 && count($failed) > 0;
            $partitioned = $this->partitionResults($imported);

            return new ImportResult(
                message: $allFailed ? 'All transaction imports failed' : 'Transactions imported successfully',
                added: $partitioned['added'],
                modified: $partitioned['modified'],
                failed: $failed,
            );
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->throwTransactionFailure('transactions', $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function importTransaction(TransactionImportItem $item): array {
        // Verify device exists
        $device = Device::query()->where('device_serial', $item->deviceId)->first();
        if ($device === null) {
            return [
                'success' => false,
                'errors' => ['device_id' => "Device with serial '{$item->deviceId}' not found"],
            ];
        }

        $sentDate = $item->sentDate ?? now();

        // Check for duplicate transaction
        $existingTransaction = Transaction::query()
            ->where('message', $item->deviceId)
            ->where('amount', $item->amount)
            ->where('created_at', $sentDate)
            ->first();

        if ($existingTransaction !== null) {
            return [
                'success' => true,
                'action' => 'modified',
                'transaction' => [
                    'id' => $existingTransaction->id,
                    'amount' => $existingTransaction->amount,
                    'device_serial' => $item->deviceId,
                    'action' => 'modified',
                ],
            ];
        }

        // Create the provider-specific transaction record matching the original type
        $transactionType = $item->transactionType ?? ThirdPartyTransaction::RELATION_NAME;
        $originalTransaction = $this->createOriginalTransaction($transactionType, $item->originalTransaction ?? []);

        $transaction = Transaction::query()->create([
            'amount' => $item->amount,
            'sender' => $item->customer ?? '',
            'message' => $item->deviceId,
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
                'device_serial' => $item->deviceId,
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
}
