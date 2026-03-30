<?php

namespace App\Plugins\SparkShs\Services;

use App\Plugins\SparkShs\Models\SparkShsCredential;
use App\Traits\EncryptsCredentials;
use Illuminate\Support\Carbon;

class SparkShsCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private SparkShsCredential $credential,
    ) {}

    public function getCredentials(): SparkShsCredential {
        $credential = $this->credential->newQuery()->firstOrCreate([], [
            'auth_url' => config('spark-shs.default_auth_url'),
            'api_url' => config('spark-shs.default_api_url'),
            'client_id' => null,
            'client_secret' => null,
        ]);

        return $this->decryptCredentialFields(
            $credential,
            ['client_id', 'client_secret']
        );
    }

    /**
     * @param array{
     *     auth_url?: string,
     *     api_url?: string,
     *     client_id?: string,
     *     client_secret?: string,
     *     access_token?: string,
     *     access_token_expires_at?: Carbon
     * } $data
     */
    public function updateCredentials(array $data): SparkShsCredential {
        $credential = $this->credential->newQuery()->firstOrFail();

        $encryptedData = $this->encryptCredentialFields(
            $data,
            ['client_id', 'client_secret']
        );

        $credential->update($encryptedData);
        $credential->save();

        $credential->fresh();

        return $this->decryptCredentialFields(
            $credential,
            ['client_id', 'client_secret']
        );
    }
}
