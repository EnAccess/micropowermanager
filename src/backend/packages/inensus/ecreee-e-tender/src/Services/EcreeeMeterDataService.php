<?php

namespace Inensus\EcreeeETender\Services;

use App\Services\TokenService;

class EcreeeMeterDataService {
    public function __construct(
        private TokenService $tokenService,
    ) {}

    public function getMeterData($startDate, $endDate) {
        return $this->tokenService->getTokensInRange($startDate, $endDate);
    }
}
