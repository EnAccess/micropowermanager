<?php

namespace Inensus\EcreeeETender\Services;

use App\Services\TokenService;
use Illuminate\Database\Eloquent\Collection;

class EcreeeMeterDataService {
    public function __construct(
        private TokenService $tokenService,
    ) {}

    public function getMeterData(?string $startDate, ?string $endDate): Collection {
        return $this->tokenService->getTokensInRange($startDate, $endDate);
    }
}
