<?php

namespace App\Providers\Helpers;

use App\Models\Transaction\AgentTransaction;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use App\Plugins\SwiftaPaymentProvider\Providers\SwiftaTransactionProvider;
use App\Plugins\WavecomPaymentProvider\Models\WaveComTransaction;
use App\Plugins\WavecomPaymentProvider\Providers\WaveComTransactionProvider;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Providers\WaveMoneyTransactionProvider;
use App\Providers\AgentTransactionProvider;
use App\Providers\Interfaces\ITransactionProvider;

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
