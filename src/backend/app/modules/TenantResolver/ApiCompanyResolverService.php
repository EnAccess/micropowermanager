<?php

declare(strict_types=1);

namespace MPM\TenantResolver;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use MPM\TenantResolver\ApiResolvers\ApiResolverInterface;
use MPM\TenantResolver\ApiResolvers\Data\ApiResolverMap;

class ApiCompanyResolverService {
    public function __construct(private ApiResolverMap $apiResolverMap) {}

    public function resolve(Request $request): int {
        $resolvableApis = $this->apiResolverMap->getResolvableApis();
        $api = collect($resolvableApis)->filter(fn (string $apiPath): bool => Str::startsWith(Str::lower($request->path()), Str::lower($apiPath)));
        if ($api->isEmpty()) {
            throw ValidationException::withMessages(['path' => 'No api resolver registered for '.$request->path()]);
        }

        $resolver = $this->startResolver($api->first());

        $companyId = $resolver->resolveCompanyId($request);
        // store company id in request attributes
        $request->attributes->add(['companyId' => $companyId]);

        return $companyId;
    }

    private function startResolver(string $api): ApiResolverInterface {
        $apiResolver = $this->apiResolverMap->getApiResolver($api);
        if ($apiResolver === null) {
            throw ValidationException::withMessages(['resolver' => 'Api is registered to resolve but no resolver class is assigned'.$api]);
        }
        /** @var ApiResolverInterface $resolver */
        $resolver = app()->make($apiResolver);

        return $resolver;
    }
}
