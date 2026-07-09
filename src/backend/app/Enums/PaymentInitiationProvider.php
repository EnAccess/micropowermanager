<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\MpmPlugin;
use App\Plugins\PaystackPaymentProvider\Services\PaystackTransactionService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use App\Plugins\SafaricomKePaymentProvider\Services\SafaricomTransactionService;
use App\Plugins\VodacomMzPaymentProvider\Services\VodacomMzTransactionService;
use App\Services\CashTransactionService;
use App\Services\Interfaces\PaymentInitiator;

// When adding a payment provider plugin that supports initiating payments
// from MPM, add a case here and map it in initiatorClass().
// The docblocks below are public and get rendered into the API docs.

/**
 * Payment providers that can be used to initiate a payment.
 * Values are MpmPlugin IDs (cash is `0`, as it needs no plugin).
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

    /** @return class-string<PaymentInitiator> */
    public function initiatorClass(): string {
        return match ($this) {
            self::Cash => CashTransactionService::class,
            self::VodacomMz => VodacomMzTransactionService::class,
            self::Paystack => PaystackTransactionService::class,
            self::Pesapal => PesapalTransactionService::class,
            self::SafaricomKe => SafaricomTransactionService::class,
        };
    }
}
