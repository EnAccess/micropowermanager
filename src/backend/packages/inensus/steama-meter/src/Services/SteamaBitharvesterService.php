<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;

class SteamaBitharvesterService {
    private SteamaMeterApiClient $steamaApi;
    private string $rootUrl = '/bitharvesters';

    public function __construct(
        SteamaMeterApiClient $steamaApi,
    ) {
        $this->steamaApi = $steamaApi;
    }

    public function getBitharvester($siteId) {
        $result = $this->steamaApi->get($this->rootUrl);
        $bitHarvesters = $result['results'];

        return array_values(array_filter($bitHarvesters, function (array $obj) use ($siteId): bool {
            return $obj['site'] === $siteId;
        }))[0];
    }
}
