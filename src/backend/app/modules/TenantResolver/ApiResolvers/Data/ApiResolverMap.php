<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers\Data;

use MPM\TenantResolver\ApiResolvers\AfricasTalkingApiResolver;
use MPM\TenantResolver\ApiResolvers\AndroidGatewayCallbackApiResolver;
use MPM\TenantResolver\ApiResolvers\DataExportResolver;
use MPM\TenantResolver\ApiResolvers\DownloadingReportsResolver;
use MPM\TenantResolver\ApiResolvers\SwiftaPaymentApiResolver;
use MPM\TenantResolver\ApiResolvers\TestApiResolver;
use MPM\TenantResolver\ApiResolvers\ViberMessagingApiResolver;
use MPM\TenantResolver\ApiResolvers\VodacomMobileMoneyApiResolver;
use MPM\TenantResolver\ApiResolvers\WaveMoneyApiResolver;

class ApiResolverMap {
    public const VIBER_API = 'api/viber-messaging/webhook';
    public const TEST_API = 'api/testApi';
    public const WAVE_MONEY_API = 'api/wave-money/wave-money-transaction';
    public const ANDROID_GATEWAY_CALLBACK_API = 'api/sms-android-callback';
    public const SWIFTA_PAYMENT_API = 'api/swifta/';
    public const REPORT_DOWNLOADING_API = 'api/report-downloading';
    public const DATA_EXPORTING_API = 'api/export';
    public const AFRICAS_TALKING_API = 'api/africas-talking/callback';
    public const VODACOM_MOBILE_MONEY = 'api/vodacom/';

    public const RESOLVABLE_APIS = [
        self::TEST_API,
        self::VIBER_API,
        self::WAVE_MONEY_API,
        self::ANDROID_GATEWAY_CALLBACK_API,
        self::SWIFTA_PAYMENT_API,
        self::REPORT_DOWNLOADING_API,
        self::DATA_EXPORTING_API,
        self::AFRICAS_TALKING_API,
        self::VODACOM_MOBILE_MONEY,
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
        self::VODACOM_MOBILE_MONEY => VodacomMobileMoneyApiResolver::class,
    ];

    public function getResolvableApis(): array {
        return self::RESOLVABLE_APIS;
    }

    public function getApiResolver(string $api): string {
        return self::API_RESOLVER[$api];
    }
}
