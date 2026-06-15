<?php

declare(strict_types=1);

namespace App\Plugins\WavecomPaymentProvider\Services;

use App\DTO\TransactionDataContainer;
use App\Jobs\ProcessPayment;
use App\Models\Transaction\Transaction;
use App\Plugins\WavecomPaymentProvider\Models\WaveComTransaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ParseCsv\Csv;

/**
 * @extends AbstractPaymentAggregatorTransactionService<WaveComTransaction>
 */
class WaveMoneyTransactionService extends AbstractPaymentAggregatorTransactionService {
    public function __construct(private Csv $csv) {}

    /** @return array<string> */
    public function createTransactionsFromFile(UploadedFile $file, ?int $companyId = null): array {
        $this->csv->auto($file->get());

        $skippedTransactions = [];

        foreach ($this->csv->data as $transactionData) {
            try {
                $this->validateTransaction($transactionData);
                $transaction = new WaveComTransaction();

                $transaction->transaction_id = $transactionData['transaction_id'];
                $transaction->sender = $transactionData['sender'];
                $transaction->message = $transactionData['message'];
                $transaction->amount = (int) $transactionData['amount'];
                $transaction->status = 0;
                $transaction->save();
            } catch (\Throwable $t) {
                if ($t instanceof QueryException) {
                    $skippedTransactions[] = $transactionData['transaction_id'].' already imported';
                } elseif ($t instanceof ValidationException) {
                    $skippedTransactions[] = $t->getMessage();
                } else {
                    $skippedTransactions[] = $transactionData['transaction_id'].' unkown reason';
                }
                continue;
            }

            $baseTransaction = new Transaction();
            $baseTransaction->amount = $transaction->amount;
            $baseTransaction->sender = $transaction->sender;
            $baseTransaction->message = $transaction->message;
            $baseTransaction->originalTransaction()->associate($transaction);
            $baseTransaction->type = Transaction::TYPE_IMPORTED;
            $baseTransaction->save();

            TransactionDataContainer::initialize($baseTransaction);

            if ($companyId !== null) {
                dispatch(new ProcessPayment($companyId, $baseTransaction->id));
            } else {
                Log::warning('Company ID not found in request attributes. Payment transaction job not triggered for transaction '.$baseTransaction->id);
            }
        }

        return $skippedTransactions;
    }

    public function setStatus(WaveComTransaction $transaction, bool $status): void {
        $mappedStatus = $status ? WaveComTransaction::STATUS_SUCCESS : WaveComTransaction::STATUS_CANCELLED;
        $transaction->status = $mappedStatus;
        $transaction->save();
    }

    /** @param array<string, mixed> $transaction */
    private function validateTransaction(array $transaction): void {
        $rules = [
            'transaction_id' => 'required',
            'sender' => 'required',
            'message' => 'required',
            'amount' => 'required',
        ];

        $validator = Validator::make($transaction, $rules);
        $validator->validate();
    }
}
