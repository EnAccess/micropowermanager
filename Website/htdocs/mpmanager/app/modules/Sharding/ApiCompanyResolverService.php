<?php

declare(strict_types=1);

namespace MPM\Sharding;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MPM\Sharding\ApiResolvers\ApiResolverInterface;
use MPM\Sharding\ApiResolvers\Data\ApiResolverMap;

class ApiCompanyResolverService
{
    public function __construct(private ApiResolverMap $apiResolverMap)
    {
    }

    public function resolve(Request $request): int
    {
        $api = collect($this->apiResolverMap::RESOLVABLE_APIS)->filter(fn ($apiPath) => Str::startsWith(Str::lower($request->path()), Str::lower($apiPath)));
        if (!$api) {
            throw new ValidationException("No api resolver registered for " . $request->path());
        }

        $resolver = $this->startResolver($api->first());

        return $resolver->resolveCompanyId($request);
    }


    private function startResolver(string $api): ApiResolverInterface
    {
        $apiResolver = $this->apiResolverMap->getApiResolver($api);
        if (!$apiResolver) {
            throw new ValidationException("Api is registered to resolve but no resolver class is assigned" . $api);
        }
        /** @var ApiResolverInterface $resolver */
        $resolver = app()->make($apiResolver);

        return $resolver;
    }
}
