<?php

declare(strict_types=1);

namespace App\Enums;

use App\Plugins\FlutterwavePaymentProvider\Providers\FlutterwavePaymentProviderServiceProvider;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveTransactionService;
use App\Plugins\PaystackPaymentProvider\Providers\PaystackPaymentProviderServiceProvider;
use App\Plugins\PaystackPaymentProvider\Services\PaystackTransactionService;
use App\Plugins\PesapalPaymentProvider\Providers\PesapalPaymentProviderServiceProvider;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use App\Plugins\SafaricomKePaymentProvider\Providers\SafaricomKePaymentProviderServiceProvider;
use App\Plugins\SafaricomKePaymentProvider\Services\SafaricomTransactionService;
use App\Plugins\VodacomMzPaymentProvider\Providers\VodacomMzPaymentProviderServiceProvider;
use App\Plugins\VodacomMzPaymentProvider\Services\VodacomMzTransactionService;
use App\Services\CashTransactionService;
use App\Services\Interfaces\PaymentInitiator;
use App\Services\ThirdPartyTransactionService;

// When adding a payment provider plugin that supports initiating payments
// from MPM, add a case here and map it in initiatorClass().
// The docblocks below are public and get rendered into the API docs.

/**
 * Payment providers that can be used to initiate a payment.
 * Values are plugin slugs, except `cash` and `third_party`, which need no installable plugin.
 */
enum PaymentInitiationProvider: string {
    private const LEGACY_IDS = [
        'cash' => 0,
        'vodacom_mz_payment_provider' => 19,
        'paystack_payment_provider' => 25,
        'pesapal_payment_provider' => 30,
        'safaricom_ke_payment_provider' => 31,
        'flutterwave_payment_provider' => 32,
        'third_party' => -1,
    ];

    /** Cash */
    case Cash = 'cash';
    /** Vodacom Mozambique M-Pesa */
    case VodacomMz = VodacomMzPaymentProviderServiceProvider::SLUG;
    /** Paystack */
    case Paystack = PaystackPaymentProviderServiceProvider::SLUG;
    /** Pesapal */
    case Pesapal = PesapalPaymentProviderServiceProvider::SLUG;
    /** Safaricom Kenya M-PESA */
    case SafaricomKe = SafaricomKePaymentProviderServiceProvider::SLUG;
    /** Flutterwave */
    case Flutterwave = FlutterwavePaymentProviderServiceProvider::SLUG;
    /**
     * A payment registered by an external party through the external transactions API
     * (see ExternalTransactionController), not selectable from the internal payment UI.
     */
    case ThirdParty = 'third_party';

    /**
     * The `payment_provider` of a request, cash when absent.
     * Accepts the legacy integer ids too, until API clients have moved to slugs.
     */
    public static function fromRequestValue(mixed $value): self {
        return self::tryFromRequestValue($value)
            ?? throw new \InvalidArgumentException('Unsupported payment provider: '.json_encode($value));
    }

    public static function tryFromRequestValue(mixed $value): ?self {
        if ($value === null || $value === '') {
            return self::Cash;
        }

        if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1)) {
            $slug = array_search((int) $value, self::LEGACY_IDS, true);

            return $slug === false ? null : self::from($slug);
        }

        return is_string($value) ? self::tryFrom($value) : null;
    }

    /**
     * Validation rule for a `payment_provider` request field.
     */
    public static function validationRule(): \Closure {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (!self::tryFromRequestValue($value) instanceof self) {
                $fail("The selected {$attribute} is invalid.");
            }
        };
    }

    /**
     * Integer id the provider had before providers were identified by slug, returned
     * alongside the slug until API clients have moved to slugs.
     */
    public function legacyId(): int {
        return self::LEGACY_IDS[$this->value];
    }

    public function requiresActivePlugin(): bool {
        return !in_array($this, [self::Cash, self::ThirdParty], true);
    }

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
