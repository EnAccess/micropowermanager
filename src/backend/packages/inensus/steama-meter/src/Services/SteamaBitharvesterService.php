<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;

class SteamaBitharvesterService {
    private string $rootUrl = '/bitharvesters';

    public function __construct(private SteamaMeterApiClient $steamaApi) {}

    public function getBitharvester($siteId) {
        $result = $this->steamaApi->get($this->rootUrl);
        $bitHarvesters = $result['results'];

        return array_values(array_filter($bitHarvesters, fn (array $obj): bool => $obj['site'] === $siteId))[0];
    }
}
