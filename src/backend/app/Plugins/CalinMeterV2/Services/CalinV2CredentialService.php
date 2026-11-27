<?php

namespace App\Plugins\CalinMeterV2\Services;

use App\Plugins\CalinMeterV2\Exceptions\CalinV2ApiResponseException;
use App\Plugins\CalinMeterV2\Http\Clients\CalinMeterV2ApiClient;
use App\Plugins\CalinMeterV2\Models\CalinV2Credential;
use App\Traits\EncryptsCredentials;

class CalinV2CredentialService {
    use EncryptsCredentials;

    private const array ENCRYPTED_FIELDS = ['company', 'user_id', 'password', 'authorization_password', 'access_token'];

    public function __construct(
        private CalinV2Credential $credential,
        private CalinMeterV2ApiClient $apiClient,
    ) {}

    public function createCredentials(): CalinV2Credential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1]);
    }

    public function getCredentials(): ?CalinV2Credential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }

    /**
     * Logs in with the new credentials before anything is saved, so a credential Calin
     * rejects never replaces one that works. The access token of that login is kept,
     * since one obtained with the previous credentials may belong to another user.
     *
     * @param array{api_url: string, company: ?string, user_id: string, password: string, authorization_password?: ?string, vend_by?: string} $data
     *
     * @throws CalinV2ApiResponseException
     */
    public function updateCredentials(array $data): CalinV2Credential {
        $login = $this->apiClient->login(new CalinV2Credential($data));
        $credential = $this->credential->newQuery()->first() ?? $this->createCredentials();

        // The login's api_url replaces the entered one, since it may have been corrected.
        $credential->update($this->encryptCredentialFields($login + $data, self::ENCRYPTED_FIELDS));

        return $this->decryptCredentialFields($credential->fresh(), self::ENCRYPTED_FIELDS);
    }

    /**
     * The stored credentials with a live access token, logging in again once the
     * token has expired.
     *
     * @throws CalinV2ApiResponseException
     */
    public function authenticatedCredentials(): CalinV2Credential {
        $credential = $this->getCredentials();

        if (!$credential instanceof CalinV2Credential || $credential->api_url === null || $credential->user_id === null || $credential->password === null) {
            throw new CalinV2ApiResponseException('Calin API V2 credentials are not configured.');
        }

        if ($credential->access_token !== null && $credential->token_expires_in !== null && $credential->token_expires_in > time()) {
            return $credential;
        }

        $login = $this->apiClient->login($credential);

        // The instance holds decrypted fields, so the token is written through a query
        // to keep the other fields from being saved in plain text.
        $this->credential->newQuery()->whereKey($credential->id)->update([
            'access_token' => $this->encryptCredentialField($login['access_token']),
            'token_expires_in' => $login['token_expires_in'],
        ]);
        $credential->access_token = $login['access_token'];
        $credential->token_expires_in = $login['token_expires_in'];

        return $credential;
    }
}
