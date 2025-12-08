<?php

namespace Inensus\EcreeeETender\Services;

use App\Services\DatabaseProxyManagerService;
use Inensus\EcreeeETender\Models\EcreeeToken;

class EcreeeTokenService {
    public function __construct(
        private EcreeeToken $ecreeeToken,
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {}

    public function getById(string $id): EcreeeToken {
        return $this->ecreeeToken->newQuery()->findOrFail($id);
    }

    public function create(): EcreeeToken {
        $data = ['token' => $this->generateToken(), 'is_active' => true];
        $ecreeeToken = $this->ecreeeToken->newQuery()->create($data);

        return $ecreeeToken->fresh();
    }

    /**
     * @param array<string, mixed> $ecreeeTokenData
     */
    public function update(EcreeeToken $ecreeeToken, array $ecreeeTokenData): EcreeeToken {
        $ecreeeToken->update($ecreeeTokenData);
        $ecreeeToken->fresh();

        return $ecreeeToken;
    }

    public function getFirst(): ?EcreeeToken {
        return $this->ecreeeToken->newQuery()->first();
    }

    private function generateToken(): string {
        $databaseProxy = $this->databaseProxyManagerService->findByEmail(auth('api')->user()->email);
        $companyId = $databaseProxy->getCompanyId();
        $randomString = bin2hex(random_bytes(4));

        return hash('sha256', $companyId.'|'.$randomString).'|'.$randomString;
    }

    public function getByToken(?string $token): ?EcreeeToken {
        return $this->ecreeeToken->newQuery()->where('token', $token)->first();
    }
}
