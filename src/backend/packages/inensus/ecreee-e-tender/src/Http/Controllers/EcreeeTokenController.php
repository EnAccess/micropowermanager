<?php

namespace Inensus\EcreeeETender\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\EcreeeETender\Http\Resources\EcreeeTokenResource;
use Inensus\EcreeeETender\Services\EcreeeTokenService;

class EcreeeTokenController extends Controller {
    public function __construct(
        private EcreeeTokenService $ecreeeTokenService,
    ) {}

    public function get(): EcreeeTokenResource {
        $ecreeeToken = $this->ecreeeTokenService->getFirst();

        if ($ecreeeToken) {
            return EcreeeTokenResource::make($ecreeeToken);
        }

        return EcreeeTokenResource::make(null);
    }

    public function store(): EcreeeTokenResource {
        $ecreeeToken = $this->ecreeeTokenService->create();

        return EcreeeTokenResource::make($ecreeeToken);
    }

    public function update(string $ecreeeTokenId): EcreeeTokenResource {
        $ecreeeToken = $this->ecreeeTokenService->getById($ecreeeTokenId);
        $isActive = $ecreeeToken->is_active;
        $updateData = ['is_active' => !$isActive];
        $ecreeeToken = $this->ecreeeTokenService->update($ecreeeToken, $updateData);

        return EcreeeTokenResource::make($ecreeeToken);
    }
}
