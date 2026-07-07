<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\MpmPlugin;

// When adding a new payment provider plugin, add a case here.
// If it also supports initiating payments from MPM, register it
// in PaymentInitiationProvider as well.
// The docblocks below are public and get rendered into the API docs.

/**
 * Payment provider plugins supported by MPM.
 * Values are MpmPlugin IDs.
 */
enum PaymentProvider: int {
    /** Swifta */
    case Swifta = MpmPlugin::SWIFTA_PAYMENT_PROVIDER;
    /** MeSomb */
    case MeSomb = MpmPlugin::MESOMB_PAYMENT_PROVIDER;
    /** Wave Money */
    case WaveMoney = MpmPlugin::WAVE_MONEY_PAYMENT_PROVIDER;
    /** Wavecom */
    case Wavecom = MpmPlugin::WAVECOM_PAYMENT_PROVIDER;
    /** Vodacom Mozambique M-Pesa */
    case VodacomMz = MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER;
    /** Paystack */
    case Paystack = MpmPlugin::PAYSTACK_PAYMENT_PROVIDER;
    /** Pesapal */
    case Pesapal = MpmPlugin::PESAPAL_PAYMENT_PROVIDER;
    /** Safaricom Kenya M-PESA */
    case SafaricomKe = MpmPlugin::SAFARICOM_KE_PAYMENT_PROVIDER;
}
