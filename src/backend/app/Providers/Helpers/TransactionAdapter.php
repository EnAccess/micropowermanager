<?php

namespace App\Providers\Helpers;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\CashTransaction;
use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use App\Plugins\FlutterwavePaymentProvider\Providers\FlutterwaveTransactionProvider;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\PaystackPaymentProvider\Providers\PaystackTransactionProvider;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Providers\PesapalTransactionProvider;
use App\Plugins\SafaricomKePaymentProvider\Models\SafaricomTransaction;
use App\Plugins\SafaricomKePaymentProvider\Providers\SafaricomKeTransactionProvider;
use App\Plugins\SmsTransactionParser\Models\SmsTransaction;
use App\Plugins\SmsTransactionParser\Providers\SmsTransactionProvider;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use App\Plugins\SwiftaPaymentProvider\Providers\SwiftaTransactionProvider;
use App\Plugins\WavecomPaymentProvider\Models\WaveComTransaction;
use App\Plugins\WavecomPaymentProvider\Providers\WaveComTransactionProvider;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Providers\WaveMoneyTransactionProvider;
use App\Providers\AgentTransactionProvider;
use App\Providers\CashTransactionProvider;
use App\Providers\Interfaces\ITransactionProvider;

class TransactionAdapter {
    /**
     * Every payment source MPM can settle, mapped to the provider that knows how to talk to it.
     * A source missing from here resolves to null and silently loses the conflict reporting and
     * SMS notification the listeners drive off it, so new payment plugins must register here.
     *
     * @var array<class-string<BasePaymentProviderTransaction>, class-string<ITransactionProvider>>
     */
    private const array PROVIDER_BY_TRANSACTION = [
        AgentTransaction::class => AgentTransactionProvider::class,
        CashTransaction::class => CashTransactionProvider::class,
        FlutterwaveTransaction::class => FlutterwaveTransactionProvider::class,
        PaystackTransaction::class => PaystackTransactionProvider::class,
        PesapalTransaction::class => PesapalTransactionProvider::class,
        SafaricomTransaction::class => SafaricomKeTransactionProvider::class,
        SmsTransaction::class => SmsTransactionProvider::class,
        SwiftaTransaction::class => SwiftaTransactionProvider::class,
        WaveComTransaction::class => WaveComTransactionProvider::class,
        WaveMoneyTransaction::class => WaveMoneyTransactionProvider::class,
    ];

    public static function getTransaction(BasePaymentProviderTransaction $transactionProvider): ?ITransactionProvider {
        foreach (self::PROVIDER_BY_TRANSACTION as $transactionClass => $providerClass) {
            if (!$transactionProvider instanceof $transactionClass) {
                continue;
            }

            $baseTransaction = resolve($providerClass);
            $baseTransaction->init($transactionProvider);

            return $baseTransaction;
        }

        return null;
    }
}
