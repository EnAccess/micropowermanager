<?php

declare(strict_types=1);

namespace MPM\Sharding\ApiResolvers\Data;

use MPM\Sharding\ApiResolvers\AfricasTalkingApiResolver;
use MPM\Sharding\ApiResolvers\AndroidGatewayCallbackApiResolver;
use MPM\Sharding\ApiResolvers\DataExportResolver;
use MPM\Sharding\ApiResolvers\DownloadingReportsResolver;
use MPM\Sharding\ApiResolvers\SwiftaPaymentApiResolver;
use MPM\Sharding\ApiResolvers\TestApiResolver;
use MPM\Sharding\ApiResolvers\ViberMessagingApiResolver;
use MPM\Sharding\ApiResolvers\WaveMoneyApiResolver;

class ApiResolverMap {
    public const VIBER_API = 'api/viber-messaging/webhook';
    public const TEST_API = 'api/testApi';
    public const WAVE_MONEY_API = 'api/wave-money/wave-money-transaction';
    public const ANDROID_GATEWAY_CALLBACK_API = 'api/sms-android-callback';
    public const SWIFTA_PAYMENT_API = 'api/swifta/';
    public const REPORT_DOWNLOADING_API = 'api/report-downloading';
    public const DATA_EXPORTING_API = 'api/export';
    public const AFRICAS_TALKING_API = 'api/africas-talking/callback';

    public const RESOLVABLE_APIS = [
        self::TEST_API,
        self::VIBER_API,
        self::WAVE_MONEY_API,
        self::ANDROID_GATEWAY_CALLBACK_API,
        self::SWIFTA_PAYMENT_API,
        self::REPORT_DOWNLOADING_API,
        self::DATA_EXPORTING_API,
        self::AFRICAS_TALKING_API,
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
    ];

    public function getResolvableApis(): array {
        return self::RESOLVABLE_APIS;
    }

    public function getApiResolver(string $api): string {
        return self::API_RESOLVER[$api];
    }
}
