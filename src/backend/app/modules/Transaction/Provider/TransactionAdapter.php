<?php

namespace MPM\Transaction\Provider;

use App\Models\Transaction\AgentTransaction;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\SwiftaPaymentProvider\Providers\SwiftaTransactionProvider;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use Inensus\WavecomPaymentProvider\Providers\WaveComTransactionProvider;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Providers\WaveMoneyTransactionProvider;

class TransactionAdapter {
    /**
     * @param ITransactionProvider $transactionProvider
     *
     * @return ITransactionProvider
     */
    public static function getTransaction($transactionProvider): ?ITransactionProvider {
        if ($transactionProvider instanceof AgentTransaction) {
            $baseTransaction = resolve(AgentTransactionProvider::class);
            $baseTransaction->init($transactionProvider);

            return $baseTransaction;
        } elseif ($transactionProvider instanceof WaveMoneyTransaction) {
            $baseTransaction = resolve(WaveMoneyTransactionProvider::class);
            $baseTransaction->init($transactionProvider);

            return $baseTransaction;
        } elseif ($transactionProvider instanceof SwiftaTransaction) {
            $baseTransaction = resolve(SwiftaTransactionProvider::class);
            $baseTransaction->init($transactionProvider);

            return $baseTransaction;
        } elseif ($transactionProvider instanceof PaystackTransaction) {
            $baseTransaction = resolve('PaystackPaymentProvider');
            $baseTransaction->init($transactionProvider);

            return $baseTransaction;
        } elseif ($transactionProvider instanceof WaveComTransaction) {
            $baseTransaction = resolve(WaveComTransactionProvider::class);
            $baseTransaction->init($transactionProvider);

            return $baseTransaction;
        }

        return null;
    }
}
