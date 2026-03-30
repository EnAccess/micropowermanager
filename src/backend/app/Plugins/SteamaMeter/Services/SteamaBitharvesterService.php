<?php

namespace App\Plugins\SteamaMeter\Services;

use App\Plugins\SteamaMeter\Http\Clients\SteamaMeterApiClient;

class SteamaBitharvesterService {
    private string $rootUrl = '/bitharvesters';

    public function __construct(
        private SteamaMeterApiClient $steamaApi,
    ) {}

    public function getBitharvester(int $siteId): mixed {
        $result = $this->steamaApi->get($this->rootUrl);
        $bitHarvesters = $result['results'];

        return array_values(array_filter($bitHarvesters, fn (array $obj): bool => $obj['site'] === $siteId))[0];
    }
}
