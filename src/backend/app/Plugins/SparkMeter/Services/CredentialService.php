<?php

namespace App\Plugins\SparkMeter\Services;

use App\Plugins\SparkMeter\Http\Requests\SparkMeterApiRequests;
use App\Plugins\SparkMeter\Models\SmCredential;
use App\Plugins\SparkMeter\Models\SmSite;
use App\Traits\EncryptsCredentials;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

class CredentialService {
    use EncryptsCredentials;
    private string $rootUrl = '/organizations';

    public function __construct(
        private SparkMeterApiRequests $sparkMeterApiRequests,
        private SmCredential $smCredential,
        private SmSite $site,
        private OrganizationService $organizationService,
    ) {}

    public function getCredentials(): ?SmCredential {
        $credential = $this->smCredential->newQuery()->latest()->take(1)->first();

        return $this->decryptCredentialFields($credential, ['api_key', 'api_secret']);
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
        $previousThunderCloudUrl = $smCredentials->thundercloud_url;

        if (!empty($data['thundercloud_url'])) {
            // Idempotent: the value round-tripped from the frontend is already normalized
            // (fromJson() just mirrors what this method last saved), so re-appending
            // unconditionally would double it to ".../api/v0/api/v0" on every re-save.
            $thunderCloudUrl = rtrim($data['thundercloud_url'], '/');
            if (!str_ends_with($thunderCloudUrl, '/api/v0')) {
                $thunderCloudUrl .= '/api/v0';
            }
            $data['thundercloud_url'] = $thunderCloudUrl;
        }

        $encryptedData = $this->encryptCredentialFields($data, ['api_key', 'api_secret']);
        $smCredentials->update($encryptedData);

        // Every site discovered under this ThunderCloud base URL shares it (one ThunderCloud
        // instance hosts several grids) — keep them in sync when the operator changes it here
        // instead of leaving them pointed at a stale host. Per-site tokens are deliberately left
        // alone: an operator can still override a single site's token after it's created.
        if ($previousThunderCloudUrl && $previousThunderCloudUrl !== $smCredentials->thundercloud_url) {
            $this->site->newQuery()->where('thundercloud_url', $previousThunderCloudUrl)
                ->update(['thundercloud_url' => $smCredentials->thundercloud_url]);
        }

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
        } catch (ClientException $cException) {
            $smCredentials->is_authenticated = $cException->getResponse()->getStatusCode() === 401 ? false : null;
        } catch (\Exception $exception) {
            Log::critical('Unknown exception while authenticating SparkMeter', ['reason' => $exception->getMessage()]);
            $smCredentials->is_authenticated = null;
        }
        $smCredentials->save();

        return $this->decryptCredentialFields($smCredentials, ['api_key', 'api_secret']);
    }
}
