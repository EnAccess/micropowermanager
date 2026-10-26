<?php

namespace App\Plugins\SparkMeter\Services;

use App\Plugins\SparkMeter\Exceptions\SparkAPIResponseException;
use App\Plugins\SparkMeter\Http\Requests\SparkMeterApiRequests;
use App\Plugins\SparkMeter\Models\SmCredential;
use App\Traits\EncryptsCredentials;
use Illuminate\Support\Facades\Log;

class CredentialService {
    use EncryptsCredentials;
    private string $rootUrl = '/organizations';

    public function __construct(
        private SparkMeterApiRequests $sparkMeterApiRequests,
        private SmCredential $smCredential,
        private OrganizationService $organizationService,
    ) {}

    public function getCredentials(): ?SmCredential {
        $credential = $this->smCredential->newQuery()->latest()->take(1)->first();

        return $this->decryptCredentialFields($credential, ['api_key', 'api_secret']);
    }

    /**
     * Authenticates the given values, falling back to the stored ones for blank
     * fields, without saving anything.
     *
     * @param array<string, mixed> $data
     *
     * @return array{valid: bool, message: string|null}
     */
    public function checkCredentials(array $data): array {
        $credential = $this->getCredentials() ?? $this->smCredential->newInstance();
        $credential->fill(array_filter($data, filled(...)));

        try {
            $this->sparkMeterApiRequests->getFromKoios($this->rootUrl, $credential);

            return ['valid' => true, 'message' => null];
        } catch (SparkAPIResponseException $exception) {
            return ['valid' => false, 'message' => $exception->getMessage()];
        }
    }

    public function isAuthenticated(): bool {
        return (bool) $this->smCredential->newQuery()->value('is_authenticated');
    }

    public function createSmCredentials(): SmCredential {
        return $this->smCredential->newQuery()->firstOrCreate(['id' => 1], [
            'api_key' => null,
            'api_secret' => null,
            'is_authenticated' => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): SmCredential {
        $smCredentials = $this->smCredential->newQuery()->find($data['id']);
        $submittedData = array_filter(
            $data,
            fn (mixed $value, string $field): bool => !in_array($field, ['api_key', 'api_secret']) || filled($value),
            ARRAY_FILTER_USE_BOTH
        );
        $encryptedData = $this->encryptCredentialFields($submittedData, ['api_key', 'api_secret']);
        $smCredentials->update($encryptedData);

        // Lets a deployment testing against a local ThunderCloud instance (with no real
        // Koios org to authenticate against) accept any api_key/api_secret instead of
        // failing every save against the real Koios cloud.
        if (config('spark-meter-integration.skip_koios_validation')) {
            $smCredentials->is_authenticated = true;
            $smCredentials->save();

            return $this->decryptCredentialFields($smCredentials, ['api_key', 'api_secret']);
        }

        try {
            $result = $this->sparkMeterApiRequests->getFromKoios($this->rootUrl);
            $smCredentials->is_authenticated = true;
            $this->organizationService->createOrganization($result['organizations'][0]);
        } catch (\Exception $exception) {
            Log::warning('SparkMeter authentication failed', ['reason' => $exception->getMessage()]);
            $smCredentials->is_authenticated = false;
        }
        $smCredentials->save();

        return $this->decryptCredentialFields($smCredentials, ['api_key', 'api_secret']);
    }
}
