<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Services;

use App\Jobs\ProcessPayment;
use App\Models\CompanyDatabase;
use App\Plugins\SmsTransactionParser\Models\SmsTransaction;
use App\Plugins\SmsTransactionParser\SmsParsing\DTO\ParsedSmsData;
use App\Plugins\SmsTransactionParser\SmsParsing\SmsParserFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class SmsTransactionService {
    public function __construct(
        private SmsParserFactory $smsParserFactory,
        private SmsTransaction $smsTransaction,
    ) {}

    public function processIncomingSms(string $body, string $sender): ?SmsTransaction {
        $parsedData = $this->smsParserFactory->parse($body, $sender);

        if (!$parsedData instanceof ParsedSmsData) {
            return null;
        }

        $existing = $this->smsTransaction->newQuery()
            ->where('transaction_reference', $parsedData->transactionReference)
            ->first();

        if ($existing) {
            Log::info('Duplicate SMS transaction skipped', [
                'reference' => $parsedData->transactionReference,
                'sender' => $sender,
            ]);

            return null;
        }

        return $this->createTransaction($parsedData);
    }

    private function createTransaction(ParsedSmsData $parsedData): ?SmsTransaction {
        try {
            /** @var SmsTransaction $smsTransaction */
            $smsTransaction = $this->smsTransaction->newQuery()->create([
                'provider_name' => $parsedData->providerName,
                'transaction_reference' => $parsedData->transactionReference,
                'amount' => $parsedData->amount,
                'sender_phone' => $parsedData->senderPhone ?? '',
                'device_serial' => $parsedData->deviceSerial,
                'raw_message' => $parsedData->rawMessage,
                'status' => SmsTransaction::STATUS_PENDING,
            ]);

            $transaction = $smsTransaction->transaction()->create([
                'amount' => $parsedData->amount,
                'sender' => $parsedData->senderPhone ?? '',
                'message' => $parsedData->deviceSerial,
                'type' => 'energy',
            ]);

            $companyId = CompanyDatabase::query()
                ->where('database_name', config('database.connections.tenant.database'))
                ->first()
                ?->getCompanyId();

            if ($companyId === null) {
                throw new \RuntimeException('Could not determine company ID for current tenant');
            }

            dispatch(new ProcessPayment($companyId, $transaction->id));

            $smsTransaction->setStatus(SmsTransaction::STATUS_SUCCESS);
            $smsTransaction->save();

            return $smsTransaction;
        } catch (\Exception $e) {
            Log::error('SMS transaction processing failed', [
                'reference' => $parsedData->transactionReference,
                'error' => $e->getMessage(),
            ]);

            if (isset($smsTransaction) && $smsTransaction->exists) {
                $smsTransaction->setStatus(SmsTransaction::STATUS_FAILED);
                $smsTransaction->save();

                $smsTransaction->conflicts()->create([
                    'state' => $e->getMessage(),
                ]);
            }

            return null;
        }
    }

    /**
     * @return LengthAwarePaginator<int, SmsTransaction>
     */
    public function getAll(int $limit = 50): LengthAwarePaginator {
        return $this->smsTransaction->newQuery()->latest()
            ->paginate($limit);
    }

    /**
     * @return LengthAwarePaginator<int, SmsTransaction>
     */
    public function getByProviderName(string $providerName, int $limit = 15): LengthAwarePaginator {
        return $this->smsTransaction->newQuery()
            ->where('provider_name', $providerName)
            ->latest()
            ->paginate($limit);
    }
}
