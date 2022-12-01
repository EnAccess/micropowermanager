<?php

declare(strict_types=1);

namespace MPM\Sharding\ApiResolvers\Data;

use MPM\Sharding\ApiResolvers\AndroidGatewayCallbackApiResolver;
use MPM\Sharding\ApiResolvers\TestApiResolver;
use MPM\Sharding\ApiResolvers\ViberMessagingApiResolver;
use MPM\Sharding\ApiResolvers\WaveMoneyApiResolver;

class ApiResolverMap
{
    public const VIBER_API = 'api/viber-messaging/webhook';
    public const TEST_API = 'api/testApi';
    public const WAVE_MONEY_API = 'api/wave-money/wave-money-transaction';
    public const ANDROID_GATEWAY_CALLBACK_API = 'api/sms-android-callback';


    public const RESOLVABLE_APIS = [
        self::TEST_API,
        self::VIBER_API,
        self::WAVE_MONEY_API,
        self::ANDROID_GATEWAY_CALLBACK_API
    ];

    private const API_RESOLVER = [
        self::TEST_API => TestApiResolver::class,
        self::VIBER_API => ViberMessagingApiResolver::class,
        self::WAVE_MONEY_API => WaveMoneyApiResolver::class,
        self::ANDROID_GATEWAY_CALLBACK_API => AndroidGatewayCallbackApiResolver::class
    ];


    public  function getResolvableApis(): array {
        return self::RESOLVABLE_APIS;
    }

    public function getApiResolver(string $api): string
    {
        return self::API_RESOLVER[$api];
    }
}
