<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 12.06.18
 * Time: 11:13
 */

namespace App\Transaction;

use App\Lib\ITransactionProvider;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\VodacomTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

class TransactionAdapter
{
    /**
     * @param ITransactionProvider $transactionProvider
     *
     * @return ITransactionProvider
     */
    public static function getTransaction($transactionProvider): ?ITransactionProvider
    {
        if ($transactionProvider instanceof VodacomTransaction) {
            $baseTransaction = resolve('VodacomPaymentProvider');
            $baseTransaction->init($transactionProvider);
            return $baseTransaction;
        }

        elseif ($transactionProvider instanceof AirtelTransaction) {
            $baseTransaction = resolve('AirtelPaymentProvider');
            $baseTransaction->init($transactionProvider);
            return $baseTransaction;
        }
        elseif ($transactionProvider instanceof AgentTransaction) {
            $baseTransaction = resolve('AgentPaymentProvider');
            $baseTransaction->init($transactionProvider);
            return $baseTransaction;
        }
        elseif ($transactionProvider instanceof WaveMoneyTransaction) {
            $baseTransaction = resolve('WaveMoneyPaymentProvider');
            $baseTransaction->init($transactionProvider);
            return $baseTransaction;
        }
        elseif ($transactionProvider instanceof SwiftaTransaction) {
            $baseTransaction = resolve('SwiftaPaymentProvider');
            $baseTransaction->init($transactionProvider);
            return $baseTransaction;
        }
        elseif ($transactionProvider instanceof WaveComTransaction) {
            /** @var ITransactionProvider $baseTransaction */
            $baseTransaction = resolve('WaveComPaymentProvider');
            $baseTransaction->init($transactionProvider);
            return $baseTransaction;
        }

        return null;
    }
}
