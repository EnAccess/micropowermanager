<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\MpmPlugin;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveTransactionService;
use App\Plugins\PaystackPaymentProvider\Services\PaystackTransactionService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use App\Plugins\SafaricomKePaymentProvider\Services\SafaricomTransactionService;
use App\Plugins\VodacomMzPaymentProvider\Services\VodacomMzTransactionService;
use App\Services\CashTransactionService;
use App\Services\Interfaces\PaymentInitiator;
use App\Services\ThirdPartyTransactionService;

// When adding a payment provider plugin that supports initiating payments
// from MPM, add a case here and map it in initiatorClass().
// The docblocks below are public and get rendered into the API docs.

/**
 * Payment providers that can be used to initiate a payment.
 * Values are MpmPlugin IDs, except Cash (`0`) and ThirdParty (`-1`), which need no
 * installable plugin and so get reserved literals instead.
 */
enum PaymentInitiationProvider: int {
    /** Cash */
    case Cash = 0;
    /** Vodacom Mozambique M-Pesa */
    case VodacomMz = MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER;
    /** Paystack */
    case Paystack = MpmPlugin::PAYSTACK_PAYMENT_PROVIDER;
    /** Pesapal */
    case Pesapal = MpmPlugin::PESAPAL_PAYMENT_PROVIDER;
    /** Safaricom Kenya M-PESA */
    case SafaricomKe = MpmPlugin::SAFARICOM_KE_PAYMENT_PROVIDER;
    /** Flutterwave */
    case Flutterwave = MpmPlugin::FLUTTERWAVE_PAYMENT_PROVIDER;
    /**
     * A payment registered by an external party through the external transactions API
     * (see ExternalTransactionController), not selectable from the internal payment UI.
     */
    case ThirdParty = -1;

    /** @return class-string<PaymentInitiator> */
    public function initiatorClass(): string {
        return match ($this) {
            self::Cash => CashTransactionService::class,
            self::VodacomMz => VodacomMzTransactionService::class,
            self::Paystack => PaystackTransactionService::class,
            self::Pesapal => PesapalTransactionService::class,
            self::SafaricomKe => SafaricomTransactionService::class,
            self::Flutterwave => FlutterwaveTransactionService::class,
            self::ThirdParty => ThirdPartyTransactionService::class,
        };
    }
}
