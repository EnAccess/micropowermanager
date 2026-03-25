<?php

declare(strict_types=1);

namespace App\Services\ApiResolvers\Data;

use App\Services\ApiResolvers\AfricasTalkingApiResolver;
use App\Services\ApiResolvers\AndroidGatewayCallbackApiResolver;
use App\Services\ApiResolvers\DataExportResolver;
use App\Services\ApiResolvers\DownloadingReportsResolver;
use App\Services\ApiResolvers\EcreeeMeterDataApiResolver;
use App\Services\ApiResolvers\OdysseyPaymentApiResolver;
use App\Services\ApiResolvers\PaystackApiResolver;
use App\Services\ApiResolvers\SwiftaPaymentApiResolver;
use App\Services\ApiResolvers\TestApiResolver;
use App\Services\ApiResolvers\TextbeeSmsGatewayApiResolver;
use App\Services\ApiResolvers\ViberMessagingApiResolver;
use App\Services\ApiResolvers\VodacomMzPaymentProviderApiResolver;
use App\Services\ApiResolvers\WaveMoneyApiResolver;

class ApiResolverMap {
    public const VIBER_API = 'api/viber-messaging/webhook';
    public const TEST_API = 'api/testApi';
    public const WAVE_MONEY_API = 'api/wave-money/wave-money-transaction';
    public const ANDROID_GATEWAY_CALLBACK_API = 'api/sms-android-callback';
    public const SWIFTA_PAYMENT_API = 'api/swifta/';
    public const REPORT_DOWNLOADING_API = 'api/report-downloading';
    public const DATA_EXPORTING_API = 'api/export';
    public const ODYSSEY_PAYMENTS_API = 'api/odyssey';
    public const AFRICAS_TALKING_API = 'api/africas-talking/callback';
    public const VODACOM_MZ_PAYMENT_PROVIDER = 'api/vodacom_mz/';
    public const PAYSTACK_API = 'api/paystack/';
    public const ECREEE_METER_DATA_API = 'api/ecreee-e-tender/ecreee-meter-data';
    public const TEXTBEE_SMS_GATEWAY_API = 'api/textbee-sms-gateway/callback';

    public const RESOLVABLE_APIS = [
        self::TEST_API,
        self::VIBER_API,
        self::WAVE_MONEY_API,
        self::ANDROID_GATEWAY_CALLBACK_API,
        self::SWIFTA_PAYMENT_API,
        self::REPORT_DOWNLOADING_API,
        self::DATA_EXPORTING_API,
        self::AFRICAS_TALKING_API,
        self::VODACOM_MZ_PAYMENT_PROVIDER,
        self::ODYSSEY_PAYMENTS_API,
        self::PAYSTACK_API,
        self::ECREEE_METER_DATA_API,
        self::TEXTBEE_SMS_GATEWAY_API,
    ];

    private const API_RESOLVER = [
        self::TEST_API => TestApiResolver::class,
        self::VIBER_API => ViberMessagingApiResolver::class,
        self::WAVE_MONEY_API => WaveMoneyApiResolver::class,
        self::ANDROID_GATEWAY_CALLBACK_API => AndroidGatewayCallbackApiResolver::class,
        self::SWIFTA_PAYMENT_API => SwiftaPaymentApiResolver::class,
        self::REPORT_DOWNLOADING_API => DownloadingReportsResolver::class,
        self::DATA_EXPORTING_API => DataExportResolver::class,
        self::AFRICAS_TALKING_API => AfricasTalkingApiResolver::class,
        self::VODACOM_MZ_PAYMENT_PROVIDER => VodacomMzPaymentProviderApiResolver::class,
        self::ODYSSEY_PAYMENTS_API => OdysseyPaymentApiResolver::class,
        self::PAYSTACK_API => PaystackApiResolver::class,
        self::ECREEE_METER_DATA_API => EcreeeMeterDataApiResolver::class,
        self::TEXTBEE_SMS_GATEWAY_API => TextbeeSmsGatewayApiResolver::class,
    ];

    /**
     * @return array<int, string>
     */
    public function getResolvableApis(): array {
        return self::RESOLVABLE_APIS;
    }

    /**
     * @return class-string|null
     */
    public function getApiResolver(string $api): ?string {
        return self::API_RESOLVER[$api] ?? null;
    }
}
