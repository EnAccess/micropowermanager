<?php

namespace MPM\Transaction\Provider;

use App\Models\Transaction\AgentTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

class TransactionAdapter {
    /**
     * @param ITransactionProvider $transactionProvider
     *
     * @return ITransactionProvider
     */
    public static function getTransaction($transactionProvider): ?ITransactionProvider {
        if ($transactionProvider instanceof AgentTransaction) {
            $baseTransaction = resolve('AgentPaymentProvider');
            $baseTransaction->init($transactionProvider);

            return $baseTransaction;
        } elseif ($transactionProvider instanceof WaveMoneyTransaction) {
            $baseTransaction = resolve('WaveMoneyPaymentProvider');
            $baseTransaction->init($transactionProvider);

            return $baseTransaction;
        } elseif ($transactionProvider instanceof SwiftaTransaction) {
            $baseTransaction = resolve('SwiftaPaymentProvider');
            $baseTransaction->init($transactionProvider);

            return $baseTransaction;
        } elseif ($transactionProvider instanceof WaveComTransaction) {
            /** @var ITransactionProvider $baseTransaction */
            $baseTransaction = resolve('WaveComPaymentProvider');
            $baseTransaction->init($transactionProvider);

            return $baseTransaction;
        }

        return null;
    }
}
