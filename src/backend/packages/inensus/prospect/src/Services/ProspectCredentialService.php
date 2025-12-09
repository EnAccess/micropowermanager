<?php

namespace Inensus\Prospect\Services;

use App\Traits\EncryptsCredentials;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Inensus\Prospect\Models\ProspectCredential;

class ProspectCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private ProspectCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): ProspectCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'api_url' => null,
            'api_token' => null,
        ]);
    }

    /**
     * @return EloquentCollection<int, ProspectCredential>|null
     */
    public function getCredentials() {
        $credentials = $this->credential->newQuery()->get();

        if ($credentials->isEmpty()) {
            return null;
        }

        return $credentials->map(fn (?object $credential): ?object => $this->decryptCredentialFields($credential, ['api_url', 'api_token']));
    }

    /**
     * @param array<int, array{id?: int, api_url: string, api_token: string}> $credentialsData
     *
     * @return Collection<int, ProspectCredential>
     */
    public function updateCredentials(array $credentialsData): Collection {
        $updatedCredentials = collect();

        $existingCredentials = $this->credential->newQuery()->get();
        $apiUrlMap = [];
        foreach ($existingCredentials as $existing) {
            $decrypted = $this->decryptCredentialFields($existing->replicate(), ['api_url', 'api_token']);
            if ($decrypted && $decrypted->api_url) {
                $apiUrlMap[$decrypted->api_url] = $existing->id;
            }
        }

        foreach ($credentialsData as $data) {
            $id = $data['id'] ?? null;
            $apiUrl = $data['api_url'] ?? null;
            $encryptedData = $this->encryptCredentialFields($data, ['api_url', 'api_token']);

            if (!$id && $apiUrl && isset($apiUrlMap[$apiUrl])) {
                $id = $apiUrlMap[$apiUrl];
            }

            if ($id) {
                $credential = $this->credential->newQuery()->updateOrCreate(
                    ['id' => $id],
                    $encryptedData
                );
            } else {
                $credential = $this->credential->newQuery()->create($encryptedData);
            }

            $credential->fresh();

            $updatedCredentials->push(
                $this->decryptCredentialFields($credential, ['api_url', 'api_token'])
            );
        }

        return $updatedCredentials;
    }
}
