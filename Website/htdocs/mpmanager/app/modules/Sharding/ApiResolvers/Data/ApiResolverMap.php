<?php

declare(strict_types=1);

namespace MPM\Sharding\ApiResolvers\Data;

use MPM\Sharding\ApiResolvers\TestApiResolver;
use MPM\Sharding\ApiResolvers\ViberMessagingApiResolver;

class ApiResolverMap
{
    public const VIBER_API = 'api/viber-messaging/webhook';
    public const TEST_API = 'api/testApi';


    public const RESOLVABLE_APIS = [
        self::TEST_API,
        self::VIBER_API
    ];

    private const API_RESOLVER = [
        self::TEST_API => TestApiResolver::class,
        self::VIBER_API => ViberMessagingApiResolver::class,
    ];


    public  function getResolvableApis(): array {
        return self::RESOLVABLE_APIS;
    }

    public function getApiResolver(string $api): string
    {
        return self::API_RESOLVER[$api];
    }
}
